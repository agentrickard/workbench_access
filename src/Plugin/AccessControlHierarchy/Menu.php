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
        );
        $params = new MenuTreeParameters();
        $data = $this->menuTree->load($id, $params);
        $tree = $this->buildTree($id, $data, $tree);
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
   * @param array &$tree
   *   The computed tree array to return.
   *
   * @return array $tree
   *   The compiled tree data.
   */
  public function buildTree($id, $data, &$tree) {
    foreach ($data as $link_id => $link) {
      $tree[$id][$link_id] = array(
        'id' => $link_id,
        'label' => $link->link->getTitle(),
        'depth' => $link->depth,
        'parents' => [],
        'weight' => $link->link->getWeight(),
        'description' => $link->link->getDescription(),
      );
      // Get the parents.
      if ($parent = $link->link->getParent()) {
        $tree[$id][$link_id]['parents'] = array_merge($tree[$id][$link_id]['parents'], [$parent]);
        $tree[$id][$link_id]['parents'] = array_merge($tree[$id][$link_id]['parents'], $tree[$id][$parent]['parents']);
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
   * @inheritdoc
   */
  public function getFields($entity_type, $bundle, $parents) {
    return ['menu' => 'Menu field'];
  }

}
