<?php

/**
 * @file
 * Contains \Drupal\workbench_access\AccessControlHierarchyBase.
 */

namespace Drupal\workbench_access;

use Drupal\workbench_access\AccessControlHierarchyInterface;
use Drupal\Component\Plugin\PluginBase;

/**
 * Defines a base hierarchy class that others may extend.
 */
abstract class AccessControlHierarchyBase extends PluginBase implements AccessControlHierarchyInterface {

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->pluginDefinition['id'];
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->pluginDefinition['label'];
  }

  /**
   * Returns the status of a hierarchy.
   */
  public function status() {

  }

  /**
   * Sets the status of a hierarchy.
   */
  public function setStatus() {

  }

  /**
   * @inheritdoc
   */
  public function options() {
    $options = array();
    if ($entity_type = $this->pluginDefinition['base_entity']) {
      $entities = entity_load_multiple($entity_type);
      foreach ($entities as $key => $entity) {
        $options[$key] = $entity->label();
      }
    }
    return $options;
  }

  /**
   * Gets the entire hierarchy tree.
   *
   * @return array
   */
  public function getTree() {
    return array();
  }

  /**
   * Loads a hierarchy definition for a single item in the tree.
   *
   * @param $id
   *   The identifier for the item, such as a term id.
   */
  public function load($id) {

  }

  /**
   * Provides configuration options.
   */
  public function configForm() {
    return array();
  }

  /**
   * Validates configuration options.
   */
  public function configValidate() {
    return array();
  }

  /**
   * Submits configuration options.
   */
  public function configSubmit() {
  }
}
