<?php

/**
 * @file
 * Contains \Drupal\workbench_access\Plugin\AccessControlHierarchy\Language.
 */

namespace Drupal\workbench_access\Plugin\AccessControlHierarchy;

use Drupal\workbench_access\AccessControlHierarchyBase;
use Drupal\workbench_access\WorkbenchAccessManagerInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines a hierarchy based on a Language.
 *
 * @AccessControlHierarchy(
 *   id = "language",
 *   module = "content_translation",
 *   base_entity = "configurable_language",
 *   entity = "configurable_language",
 *   label = @Translation("Language"),
 *   description = @Translation("Uses content language as an access control hierarchy.")
 * )
 */
class Language extends AccessControlHierarchyBase {

  /**
   * The access tree array.
   *
   * @var array
   */
  public $tree;

  /**
   * @inheritdoc
   */
  public function getTree() {
    if (!isset($this->tree)) {
      $config = $this->config('workbench_access.settings');
      $parents = $config->get('parents');
      $tree = array();
      $this->menuTree = \Drupal::getContainer()->get('language.manager');
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
          $this->tree = $this->buildTree($id, $data, $tree);
        }
      }
    }
    return $this->tree;
  }

  /**
   * @inheritdoc
   */
  public function getFields($entity_type, $bundle, $parents) {
    return ['language' => 'Language field'];
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
    $values = array();
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
       'real_field' => 'id',
      ];
    }
    return $configuration;
  }

}
