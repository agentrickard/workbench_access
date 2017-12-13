<?php

namespace Drupal\workbench_access_test\Plugin\AccessControlHierarchy;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\workbench_access\AccessControlHierarchyBase;
use Drupal\workbench_access\Entity\AccessSchemeInterface;
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
  protected function getFields($entity_type, $bundle) {
    return ['uid' => 'User'];
  }

  /**
   * {@inheritdoc}
   */
  public function alterOptions(AccessSchemeInterface $scheme, $field, array $user_sections = []) {
    return $field;
  }

  /**
   * {@inheritdoc}
   */
  public function applies($entity_type_id, $bundle) {
    return TRUE;
  }

}
