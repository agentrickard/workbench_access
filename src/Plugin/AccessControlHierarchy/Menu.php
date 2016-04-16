<?php

/**
 * @file
 * Contains \Drupal\workbench_access\Plugin\AccessControlHierarchy\Menu.
 */

namespace Drupal\workbench_access\Plugin\AccessControlHierarchy;

use Drupal\workbench_access\AccessControlHierarchyBase;
use Drupal\workbench_access\WorkbenchAccessManagerInterface;
use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\system\Entity\Menu as MenuEntity;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Menu\MenuLinkInterface;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\Core\Menu\MenuLinkTreeElement;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Menu\MenuTreeStorageInterface;

/**
 * Defines a hierarchy based on a Menu.
 *
 * @AccessControlHierarchy(
 *   id = "menu",
 *   module = "menu_ui",
 *   base_entity = "menu",
 *   entity = "menu_link_content",
 *   label = @Translation("Menu"),
 *   description = @Translation("Uses a menu as an access control hierarchy.")
 * )
 */
class Menu extends AccessControlHierarchyBase {

  /**
   * @inheritdoc
   */
  public function getTree() {
    $config = $this->config('workbench_access.settings');
    $parents = $config->get('parents');
    $tree = array();
    $this->menuTree = \Drupal::getContainer()->get('menu.link_tree');
    foreach ($parents as $id => $label) {
      if ($menu = MenuEntity::load($id)) {
        $tree[$id][$id] = array(
          'label' => $menu->label(),
          'depth' => 0,
          'parents' => [],
          'weight' => 0,
          'description' => $menu->label(),
          'id' => $id,
        );
        $params = new MenuTreeParameters();
        $data = $this->menuTree->load($id, $params);
        $map = $this->loadMenuLinkData($id);
        $tree = $this->buildTree($id, $data, $map, $tree);
      }
    }
    return $tree;
  }

  /**
   * Traverses the menu link tree and builds parentage arrays.
   *
   * Note: this method is necessary because Menu does not auto-load parents.
   *
   * @param $id
   *   The root id of the section tree.
   * @param array $data
   *   An array of menu tree or subtree data.
   * @param array $map
   *   An array of menu information, key is string id, value is integer mlid.
   * @param array &$tree
   *   The computed tree array to return.
   *
   * @return array $tree
   *   The compiled tree data.
   */
  public function buildTree($id, $data, $map, &$tree) {
    foreach ($data as $link_id => $link) {
      $mlid = $map[$link_id];
      $tree[$id][$mlid] = array(
        'id' => $mlid, // This is the numeric mlid.
        'label' => $link->link->getTitle(),
        'depth' => $link->depth,
        'parents' => [],
        'weight' => $link->link->getWeight(),
        'description' => $link->link->getDescription(),
      );
      // Get the parents.
      if ($parent = $link->link->getParent()) {
        $tree[$id][$mlid]['parents'] = array_merge($tree[$id][$mlid]['parents'], [$map[$parent]]);
        $tree[$id][$mlid]['parents'] = array_merge($tree[$id][$mlid]['parents'], $tree[$id][$map[$parent]]['parents']);
      }
      else {
        $tree[$id][$mlid]['parents'] = [$id];
      }
      if (isset($link->subtree)) {
        $this->buildTree($id, $link->subtree, $map, $tree);
      }
    }
    return $tree;
  }

  /**
   * @inheritdoc
   */
  public function getFields($entity_type, $bundle, $parents) {
    return ['menu' => 'Menu field'];
  }

  /**
   * {@inheritdoc}
   */
  public function alterOptions($field, WorkbenchAccessManagerInterface $manager) {
    $element = $field;
    $user_sections = $manager->getUserSections();
    $menu_check = [];
    foreach ($element['link']['menu_parent']['#options'] as $id => $data) {
      // The menu value here prepends the menu name. Remove that.
      $parts = explode(':', $id);
      $menu = array_shift($parts);
      $sections = [implode(':', $parts)];
      // Remove unusable elements, except the existing parent.
      if ((!empty($element['link']['menu_parent']['#default_value']) && $id != $element['link']['menu_parent']['#default_value']) && empty($manager->checkTree($sections, $user_sections))) {
        unset($element['link']['menu_parent']['#options'][$id]);
      }
      // Check for the root menu item.
      if (!isset($menu_check[$menu]) && isset($element['link']['menu_parent']['#options'][$menu . ':'])) {
        if (empty($manager->checkTree([$menu], $user_sections))) {
          unset($element['link']['menu_parent']['#options'][$menu . ':']);
        }
        $menu_check[$menu] = TRUE;
      }
    }
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityValues(EntityInterface $entity, $field) {
    static $map;
    $values = array();
    $defaults = menu_ui_get_menu_link_defaults($entity);
    // Because the default menu system doesn't return mlids, we have to do some
    // handstands.
    if (!empty($defaults['id'])) {
      if (!isset($map[$defaults['menu_name']])) {
        $map[$defaults['menu_name']] = $this->loadMenuLinkData($defaults['menu_name']);
      }
      $values = [$map[$defaults['menu_name']][$defaults['id']]];
    }
    return $values;
  }

  /**
   * {inheritdoc}
   */
  public function disallowedOptions($field) {
    // On the menu form, we never remove an existing parent item, so there is
    // no concept of a disallowed option.
    return array();
  }

  /**
   * {inheritdoc}
   */
  public function getViewsJoin($table, $key, $alias = NULL) {
    if ($table == 'users') {
      $configuration['menu'] = [
       'table' => 'user__' . WORKBENCH_ACCESS_FIELD,
       'field' => 'entity_id',
       'left_table' => $table,
       'left_field' => $key,
       'operator' => '=',
       'table_alias' => WORKBENCH_ACCESS_FIELD,
       'real_field' => WORKBENCH_ACCESS_FIELD . '_value',
      ];
    }
    else {
      $configuration['menu'] = [
       'table' => 'menu_tree',
       'field' => 'route_param_key',
       'left_table' => $table,
       'left_field' => $key,
       'left_query' => "CONCAT('{$table}=', {$alias}.{$key})",
       'operator' => '=',
       'table_alias' => 'menu_tree',
       'real_field' => 'mlid',
      ];
    }
    return $configuration;
  }

  /**
   * Loads menu data that include mlids, which we need for storage.
   *
   * @param $id
   *   The root id of the section tree.
   *
   * @return array
   *   An array of menu information, key is string id, value is integer mlid.
   */
  private function loadMenuLinkData($id) {
    $storage = \Drupal::getContainer()->get('menu.tree_storage');
    $data = $storage->loadByProperties(['menu_name' => $id]);
    foreach ($data as $link) {
      $map[$link['id']] = $link['mlid'];
    }
    return $map;
  }

}
