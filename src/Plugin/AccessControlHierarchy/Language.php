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
      $tree = array(
        'languages' => array(
          'languages' => array(
            'label' => 'All languages',
            'depth' => 0,
            'parents' => [],
            'weight' => 0,
            'description' => 'All languages',
          ),
        ),
      );
      $languages = $entities = entity_load_multiple('configurable_language');
      foreach ($languages as $id => $language) {
        $tree['languages'][$id] = array(
          'label' => $language->getName(),
          'depth' => 1,
          'parents' => ['languages'],
          'weight' => 0,
          'description' => $language->getName(),
        );
      }
      $this->tree = $tree;
    }
    return $this->tree;
  }

  /**
   * @inheritdoc
   */
  public function getFields($entity_type, $bundle, $parents) {
    return ['language' => 'Language field'];
  }

  public function options() {
    return ['languages' => 'Enabled languages'];
  }

  /**
   * {@inheritdoc}
   *
   * @TODO -- fix this
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
   * @TODO -- fix this
   */
  public function disallowedOptions($field) {
    // On the language form, we never remove an existing parent item, so there is
    // no concept of a disallowed option.
    return array();
  }

  /**
   * {inheritdoc}
   * @TODO -- fix this
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
