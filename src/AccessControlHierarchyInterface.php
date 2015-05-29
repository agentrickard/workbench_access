<?php

/**
 * @file
 * Contains \Drupal\workbench_access\AccessControlHierarchyInterface.
 */

namespace Drupal\workbench_access;

/**
 * Defines a base hierarchy class that others may extend.
 */
interface AccessControlHierarchyInterface {

  /**
   * Returns the label for a hierarchy.
   */
  public function label();

  /**
   * Returns the status of a hierarchy.
   */
  public function status();

  /**
   * Sets the status of a hierarchy.
   */
  public function setStatus();

  /**
   * Gets the entire hierarchy tree.
   *
   * @return array
   */
  public function getTree();

  /**
   * Loads a hierarchy definition.
   */
  public function load();

  /**
   * Provides configuration options.
   */
  public function configForm();

  /**
   * Validates configuration options.
   */
  public function configValidate();

  /**
   * Submits configuration options.
   */
  public function configSubmit();

}
