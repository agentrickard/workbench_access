<?php

namespace Drupal\workbench_access;

use Drupal\workbench_access\WorkbenchAccessManager;
use Drupal\workbench_access\WorkbenchAccessManagerInterface;
use Drupal\workbench_access\Plugin\views\filter\Section;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines a base hierarchy class that others may extend.
 */
interface AccessControlHierarchyInterface {

  /**
   * Returns the id for a hierarchy.
   *
   * @return string
   */
  public function id();

  /**
   * Returns the label for a hierarchy.
   *
   * @return string
   */
  public function label();

  /**
   * Returns the status of a hierarchy.
   *
   * @return boolean
   */
  public function status();

  /**
   * Gets the options for a hierarchy.
   *
   * @return array
   *   In the format id => label.
   */
  public function options();

  /**
   * Gets the entire hierarchy tree.
   *
   * @return array
   */
  public function getTree();

  /**
   * Gets the fields that may be used for a plugin type.
   *
   * This method informs the system what fields are eligible to use for
   * access controls. For instance, with taxonomy, it returns all taxonomy
   * reference fields.
   *
   * @param $entity_type
   *   The type of entity access control is being tested for (e.g. 'node').
   * @param $bundle
   *   The entity bundle being tested (e.g. 'article').
   * @param $parents
   *   The selected parent roots of the hierarchy. e.g. a taxonomy vocabulary.
   *   The array contains the ids of the root items (e.g. a vocabulary id).
   *
   * @return array
   *   An array of fields in the format id => label, for use in a form.
   */
  public function getFields($entity_type, $bundle, $parents);

  /**
   * Retrieves the access control values from an entity.
   *
   * @param EntityInterface $entity
   *   A Drupal entity, typically a node or a user.
   * @param $field
   *   The field holding the access control data.
   *
   * @return array
   *   An array of field data from the entity.
   */
  public function getEntityValues(EntityInterface $entity, $field);

  /**
   * Loads a hierarchy definition for a single item in the tree.
   *
   * @param $id
   *   The identifier for the item, such as a term id.
   *
   * @return \Drupal\workbench_access\AccessControlHierarchyInterface
   *   A plugin implementation.
   */
  public function load($id);

  /**
   * Provides configuration options.
   *
   * @param $parents
   *   The selected parent roots of the hierarchy. e.g. a taxonomy vocabulary.
   *   The array contains the ids of the root items (e.g. a vocabulary id).
   */
  public function configForm($parents = array());

  /**
   * Validates configuration options.
   *
   * @param array &$form
   *   The submitted form, passed by reference.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return
   *   No return value. Interact with the $form_state object.
   */
  public function configValidate(array &$form, FormStateInterface $form_state);

  /**
   * Submits configuration options.
   *
   * @param array &$form
   *   The submitted form, passed by reference.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  public function configSubmit(array &$form, FormStateInterface $form_state);

  /**
   * Responds to request for node access.
   *
   * @param EntityInterface $entity
   *   The node being checked. In future this may handle other entity types.
   * @param AccountInterface $account
   *   The user requesting access to the node.
   * @param WorkbenchAccessManagerInterface $manager
   *   The access control manager.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   An access result response. By design, this is either neutral or deny.
   *
   * @see workbench_access_node_access()
   */
  public function checkEntityAccess(EntityInterface $entity, $op, AccountInterface $account, WorkbenchAccessManagerInterface $manager);

  /**
   * Alters the selection options provided for an access control field.
   *
   * @param $field
   *   The field element from a node form.
   * @param WorkbenchAccessManagerInterface $manager
   *   The access manager.
   * @param array $user_sections
   *   The user sections.
   *
   * @return $element
   *   The field element, after restricting selection options.
   */
  public function alterOptions($field, WorkbenchAccessManagerInterface $manager, array $user_sections = []);

  /**
   * Gets any options that are set but cannot be changed by the editor.
   *
   * @param $field
   *   The field element from a node form, after running through alterOptions().
   *
   * @return array
   *   An array of section ids to preserve.
   */
  public function disallowedOptions($field);

  /**
   * Responds to the submission of an entity form.
   *
   * If the entity contains section values that the user cannot change, they
   * are passed in the 'workbench_access_disallowed' field on the form. Plugins
   * should examine that value and make modifications to their target field
   * as necessary.
   *
   * Currently only supports nodes. A default implementation is provided.
   *
   * @param array &$form
   *   A form array.
   * @param FormStateInterface $form_state
   *   The form_state object.
   */
  public static function submitEntity(array &$form, FormStateInterface $form_state);

  /**
   * Returns information about how to join this section data to a base view table.
   *
   * @param $table
   *   The base table of the view.
   * @param $key
   *   The primary key of the base table.
   * @param $alias (optional)
   *   The views alias of the base table.
   *
   * @return array
   *   The configuration array for adding a views JOIN statement.
   */
  public function getViewsJoin($table, $key, $alias = NULL);

  /**
   * Adds a where clause to a view when using a section filter.
   *
   * @param Drupal\workbench_access\Plugin\views\filter\Section $filter
   *   The views filter object provided by Workbench Access.
   * @param $values
   *   An array of values for the current view.
   */
  public function addWhere(Section $filter, $values);

  /**
   * Resets the internal cache of the tree.
   *
   * Right now, this is a per-request cache until we figure out a long-term
   * caching strategy.
   */
  public function resetTree();

  /**
   * Returns the access control fields configured for use by the plugin.
   *
   * @param $entity_type
   *   The type of entity access control is being tested for (e.g. 'node').
   * @param $bundle
   *   The entity bundle being tested (e.g. 'article').
   */
  public function fields($entity_type, $bundle);

  /**
   * Returns the access control fields configured for use by the plugin.
   *
   * @param $entity_type
   *   The type of entity access control is being tested for (e.g. 'node').
   */
  public function fieldsByEntityType($entity_type);

}
