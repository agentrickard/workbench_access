<?php

namespace Drupal\workbench_access\Plugin\AccessControlHierarchy;

use Drupal\workbench_access\AccessControlHierarchyBase;
use Drupal\workbench_access\Entity\AccessSchemeInterface;
use Drupal\workbench_access\WorkbenchAccessManager;
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

/**
 * Defines a hierarchy based on a Menu.
 *
 * @AccessControlHierarchy(
 *   id = "menu",
 *   module = "menu_ui",
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
    if (!isset($this->tree)) {
      $parents = $this->config->get('parents');
      $tree = [];
      $this->menuTree = \Drupal::getContainer()->get('menu.link_tree');
      foreach ($parents as $id => $label) {
        if ($menu = MenuEntity::load($id)) {
          $tree[$id][$id] = [
            'label' => $menu->label(),
            'depth' => 0,
            'parents' => [],
            'weight' => 0,
            'description' => $menu->label(),
          ];
          $params = new MenuTreeParameters();
          $data = $this->menuTree->load($id, $params);
          $this->tree = $this->buildTree($id, $data, $tree);
        }
      }
    }
    return $this->tree;
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
   * @param array &$tree
   *   The computed tree array to return.
   *
   * @return array $tree
   *   The compiled tree data.
   */
  protected function buildTree($id, $data, &$tree) {
    foreach ($data as $link_id => $link) {
      $tree[$id][$link_id] = [
        'id' => $link_id,
        'label' => $link->link->getTitle(),
        'depth' => $link->depth,
        'parents' => [],
        'weight' => $link->link->getWeight(),
        'description' => $link->link->getDescription(),
      ];
      // Get the parents.
      if ($parent = $link->link->getParent()) {
        $tree[$id][$link_id]['parents'] = array_unique(array_merge($tree[$id][$link_id]['parents'], [$parent]));
        $tree[$id][$link_id]['parents'] = array_unique(array_merge($tree[$id][$link_id]['parents'], $tree[$id][$parent]['parents']));
      }
      else {
        $tree[$id][$link_id]['parents'] = [$id];
      }
      if (isset($link->subtree)) {
        $this->buildTree($id, $link->subtree, $tree);
      }
    }
    return $tree;
  }

  /**
   * {@inheritdoc}
   */
  public function alterOptions(AccessSchemeInterface $scheme, $field, array $user_sections = []) {
    $element = $field;
    $menu_check = [];
    foreach ($element['link']['menu_parent']['#options'] as $id => $data) {
      // The menu value here prepends the menu name. Remove that.
      $parts = explode(':', $id);
      $menu = array_shift($parts);
      $sections = [implode(':', $parts)];
      // Remove unusable elements, except the existing parent.
      if ((!empty($element['link']['menu_parent']['#default_value']) && $id != $element['link']['menu_parent']['#default_value']) && empty(WorkbenchAccessManager::checkTree($sections, $user_sections, $this->getTree()))) {
        unset($element['link']['menu_parent']['#options'][$id]);
      }
      // Check for the root menu item.
      if (!isset($menu_check[$menu]) && isset($element['link']['menu_parent']['#options'][$menu . ':'])) {
        if (empty(WorkbenchAccessManager::checkTree($scheme, [$menu], $user_sections))) {
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
  public function getEntityValues(EntityInterface $entity) {
    $values = [];
    $defaults = menu_ui_get_menu_link_defaults($entity);
    if (!empty($defaults['id'])) {
      $values = [$defaults['id']];
    }
    return $values;
  }

  /**
   * {inheritdoc}
   */
  public function disallowedOptions($field) {
    // On the menu form, we never remove an existing parent item, so there is
    // no concept of a disallowed option.
    return [];
  }

  /**
   * {inheritdoc}
   */
  public function getViewsJoin($table, $key, $alias = NULL) {
    if ($table == 'users') {
      $configuration['menu'] = [
       'table' => 'user__' . WorkbenchAccessManagerInterface::FIELD_NAME,
       'field' => 'entity_id',
       'left_table' => $table,
       'left_field' => $key,
       'operator' => '=',
       'table_alias' => WorkbenchAccessManagerInterface::FIELD_NAME,
       'real_field' => WorkbenchAccessManagerInterface::FIELD_NAME . '_value',
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
       'real_field' => 'id',
      ];
    }
    return $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function applies($entity_type_id, $bundle) {
    // @todo change configuration
    return $entity_type_id === 'node' && in_array($bundle, $this->configuration['bundles']);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    // TODO: Implement submitConfigurationForm() method.
  }

}
