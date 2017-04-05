<?php

namespace Drupal\workbench_access;

/**
 * Defines an interface handling Workbench Access configuration.
 */
interface WorkbenchAccessManagerInterface extends UserSectionStorageInterface, RoleSectionStorageInterface {
  const FIELD_NAME = 'field_workbench_access';

  /**
   * Returns an array of available access schemes.
   *
   * @return \Drupal\workbench_access\AccessControlHierarchyInterface[]
   *   An array of schemes.
   */
  public function getSchemes();

  /**
   * Gets a single access scheme.
   *
   * Access schemes are defined by AccessControlHierarchy plugins.
   *
   * @param $id
   *   A string indicating the plugin name.
   *
   * @return \Drupal\workbench_access\AccessControlHierarchyInterface
   *   The scheme identified by the id.
   */
  public function getScheme($id);

  /**
   * Gets the active access scheme, as set in module configuration.
   *
   * @return \Drupal\workbench_access\AccessControlHierarchyInterface
   *   The active scheme.
   */
  public function getActiveScheme();

  /**
   * Gets the hierarchy tree defined by an access control plugin.
   *
   * @return array
   *   The array will be keyed by the id of each element, and contain the
   *   following data:
   *   - label (string) -- The human-readable label of the element.
   *   - depth (int) -- The depth of the element in the tree.
   *   - parents (array) -- An array of parent elements for this element.
   *   - weight (int) -- The sort weight of the element.
   *   - description (text) -- An optional text description of the element.
   *
   * @TODO: Convert this to a classed object.
   */
  public function getActiveTree();

  /**
   * Gets a single element from the active access tree.
   *
   * @param $id
   *   The id of the item to return.
   *
   * @return array
   *   An array as described by getActiveTree().
   */
  public function getElement($id);

  /**
   * Get the default value for a workbench access form element.
   *
   * Note that this function only applies to our test field.
   *
   * @return array
   */
  public static function getDefaultValue();

  /**
   * Adds a set of sections to an entity.
   *
   * @param int $entity_id
   *   An entity id.
   * @param $entity_type
   *   The entity type.
   * @param array $sections
   *   An array of section ids to assign to this entity.
   */
  public function addEntity($entity_id, $entity_type, $sections = array());

  /**
   * Removes a set of sections to an entity.
   *
   * @param int $entity_id
   *   An entity id.
   * @param $entity_type
   *   The entity type.
   * @param array $sections
   *   An array of section ids to assign to this entity.
   */
  public function removeEntity($entity_id, $entity_type, $sections = array());

  /**
   * Checks that an entity belongs to a user section or its children.
   *
   * @param array $entity_sections
   *   The section assignments for the entity. An array of section ids.
   * @param array $user_sections
   *   The section assignements for the user. An array of section ids.
   *
   * return boolean
   */
  public function checkTree($entity_sections, $user_sections);

  /**
   * Returns a flat array of all active section ids.
   *
   * Used to display assignments for admins.
   *
   * @param boolean $root_only
   *   If TRUE, only show the root-level assignments.
   *
   * @return array
   */
  public function getAllSections($root_only = FALSE);

  /**
   * Determines if a user is assigned to all sections.
   *
   * This method checks the permissions and assignments for a user. Someone set
   * as an admin or with access to the top-level sections is assumed to be able
   * to access all sections. We use this logic in query filtering.
   *
   * @param $uid
   *   An optional user id. If not provided, the active user is returned.
   *
   * @return boolean
   */
  public function userInAll($uid = NULL);

  /**
   * Removes all field settings.
   *
   * This method should be triggered when changing access schemes. If possible,
   * let the administrator choose to run this.
   */
  public function flushFields();

  /**
   * Resets the internal cache of the tree.
   *
   * Right now, this is a per-request cache until we figure out a long-term
   * caching strategy.
   */
  public function resetTree();

}
