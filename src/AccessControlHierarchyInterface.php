<?php

namespace Drupal\workbench_access;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\workbench_access\Entity\AccessSchemeInterface;
use Drupal\workbench_access\Plugin\views\filter\Section;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Plugin\PluginWithFormsInterface;

/**
 * Defines a base hierarchy class that others may extend.
 */
interface AccessControlHierarchyInterface extends ConfigurablePluginInterface, PluginWithFormsInterface, PluginFormInterface {

  /**
   * Returns the id for a hierarchy.
   *
   * @return string
   *   Access control ID.
   */
  public function id();

  /**
   * Returns the label for a hierarchy.
   *
   * @return string
   *   Label.
   */
  public function label();

  /**
   * Gets the entire hierarchy tree.
   *
   * @return array
   *   Tree.
   */
  public function getTree();

  /**
   * Retrieves the access control values from an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   A Drupal entity, typically a node or a user.
   *
   * @return array
   *   An array of field data from the entity.
   */
  public function getEntityValues(EntityInterface $entity);

  /**
   * Loads a hierarchy definition for a single item in the tree.
   *
   * @param string $id
   *   The identifier for the item, such as a term id.
   *
   * @return \Drupal\workbench_access\AccessControlHierarchyInterface
   *   A plugin implementation.
   */
  public function load($id);

  /**
   * Responds to request for node access.
   *
   * @param \Drupal\workbench_access\Entity\AccessSchemeInterface $scheme
   *   Access scheme.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The node being checked. In future this may handle other entity types.
   * @param string $op
   *   The operation, e.g. update, delete.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user requesting access to the node.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   An access result response. By design, this is either neutral or deny.
   *
   * @see workbench_access_entity_access()
   */
  public function checkEntityAccess(AccessSchemeInterface $scheme, EntityInterface $entity, $op, AccountInterface $account);

  /**
   * Alters the selection options provided for an access control field.
   *
   * @param \Drupal\workbench_access\Entity\AccessSchemeInterface $scheme
   *   Access scheme.
   * @param array $form
   *   The content entry form to alter.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Active form state data.
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity object that the form is modifying.
   */
  public function alterForm(AccessSchemeInterface $scheme, array &$form, FormStateInterface &$form_state, ContentEntityInterface $entity);

  /**
   * Gets any options that are set but cannot be changed by the editor.
   *
   * @param string $field
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
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form_state object.
   */
  public static function submitEntity(array &$form, FormStateInterface $form_state);

  /**
   * Returns information on how to join this section data to a base view table.
   *
   * @param string $entity_type
   *   The base table of the view.
   * @param string $key
   *   The primary key of the base table.
   * @param string $alias
   *   The views alias of the base table.
   *
   * @return array
   *   The configuration array for adding a views JOIN statement.
   */
  public function getViewsJoin($entity_type, $key, $alias = NULL);

  /**
   * Adds a where clause to a view when using a section filter.
   *
   * @param \Drupal\workbench_access\Plugin\views\filter\Section $filter
   *   The views filter object provided by Workbench Access.
   * @param array $values
   *   An array of values for the current view.
   */
  public function addWhere(Section $filter, array $values);

  /**
   * Resets the internal cache of the tree.
   *
   * Right now, this is a per-request cache until we figure out a long-term
   * caching strategy.
   */
  public function resetTree();

  /**
   * Check if this access scheme applies to the given entity.
   *
   * @param string $entity_type_id
   *   Entity type ID.
   * @param string $bundle
   *   Bundle ID.
   *
   * @return bool
   *   TRUE if this access scheme applies to the entity.
   */
  public function applies($entity_type_id, $bundle);

  /**
   * Adds views data for the plugin.
   *
   * @param array $data
   *   Views data.
   * @param \Drupal\workbench_access\Entity\AccessSchemeInterface $scheme
   *   Access scheme that wraps this plugin.
   */
  public function viewsData(array &$data, AccessSchemeInterface $scheme);

  /**
   * Massage form values as appropriate.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   Entity being edited.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   * @param array $hidden_values
   *   Hidden values.
   */
  public function massageFormValues(ContentEntityInterface $entity, FormStateInterface $form_state, array $hidden_values);

  /**
   * Informs the plugin that a dependency of the scheme will be deleted.
   *
   * @param array $dependencies
   *   An array of dependencies that will be deleted keyed by dependency type.
   *
   * @return bool
   *   TRUE if the workflow settings have been changed, FALSE if not.
   *
   * @see \Drupal\Core\Config\ConfigEntityInterface::onDependencyRemoval()
   *
   * @todo https://www.drupal.org/node/2579743 make part of a generic interface.
   */
  public function onDependencyRemoval(array $dependencies);

}
