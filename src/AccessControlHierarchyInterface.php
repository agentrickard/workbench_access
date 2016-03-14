<?php

/**
 * @file
 * Contains \Drupal\workbench_access\AccessControlHierarchyInterface.
 */

namespace Drupal\workbench_access;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\workbench_access\WorkbenchAccessManager;

/**
 * Defines a base hierarchy class that others may extend.
 */
interface AccessControlHierarchyInterface {

  /**
   * Returns the id for a hierarchy.
   */
  public function id();

  /**
   * Returns the label for a hierarchy.
   */
  public function label();

  /**
   * Returns the status of a hierarchy.
   */
  public function status();

  /**
   * Gets the options for a hierarchy.
   */
  public function options();

  /**
   * Gets the entire hierarchy tree.
   *
   * @return array
   */
  public function getTree();

  /**
   * Loads a hierarchy definition for a single item in the tree.
   *
   * @param $id
   *   The identifier for the item, such as a term id.
   */
  public function load($id);

  /**
   * Provides configuration options.
   */
  public function configForm($scheme, $parents = array());

  /**
   * Validates configuration options.
   */
  public function configValidate();

  /**
   * Submits configuration options.
   */
  public function configSubmit(array &$form, FormStateInterface $form_state);

  /**
   * Responds to request for node access.
   *
   * @see workbench_access_node_access()
   */
  public function checkEntityAccess(EntityInterface $entity, $op, AccountInterface $account, WorkbenchAccessManager $manager);

}
