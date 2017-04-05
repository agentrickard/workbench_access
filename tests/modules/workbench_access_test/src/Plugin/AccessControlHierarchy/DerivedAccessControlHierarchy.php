<?php

namespace Drupal\workbench_access_test\Plugin\AccessControlHierarchy;

use Drupal\workbench_access\AccessControlHierarchyBase;
use Drupal\workbench_access\WorkbenchAccessManagerInterface;

/**
 * Defines a hierarchy based on an entity hierarchy field.
 *
 * @AccessControlHierarchy(
 *   id = "workbench_access_test_derived",
 *   module = "workbench_access_test",
 *   deriver = "Drupal\workbench_access_test\Plugin\Derivative\DerivedAccessControlPlugins",
 *   label = @Translation("Derived plugins"),
 *   description = @Translation("Uses derivatives for plugins.")
 * )
 */
class DerivedAccessControlHierarchy extends AccessControlHierarchyBase {

  /**
   * {@inheritdoc}
   */
  public function getFields($entity_type, $bundle, $parents) {
    return ['uid' => 'User'];
  }

  /**
   * {@inheritdoc}
   */
  public function alterOptions($field, WorkbenchAccessManagerInterface $manager, array $user_sections = []) {
    return $field;
  }

}
