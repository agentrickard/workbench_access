<?php

/**
 * @file
 * Contains \Drupal\workbench_access\WorkbenchAccessManagerInterface.
 */

namespace Drupal\workbench_access;

interface WorkbenchAccessManagerInterface {

  /**
   * Returns an array of available access schemes.
   *
   * @return array
   *   An array of schemes as defined by
   *   \Drupal\workbench_access\Annotation\AccessControlHierarchy.
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
   * @return \Drupal\workbench_access\Annotation\AccessControlHierarchy
   */
  public function getScheme($id);

  /**
   * Gets the active access scheme, as set in module configuration.
   *
   * @return \Drupal\workbench_access\Annotation\AccessControlHierarchy
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
   * Adds a set of sections to a user.
   *
   * @param int $user_id
   *   A user id.
   * @param array $sections
   *   An array of section ids to assign to this user.
   */
  public function addUser($user_id, $sections = array());

  /**
   * Adds a set of sections to a role.
   *
   * @param int $role_id
   *   A role id.
   * @param array $sections
   *   An array of section ids to assign to this role.
   */
  public function addRole($role_id, $sections = array());

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
   * Removes a set of sections to a user.
   *
   * @param int $user_id
   *   A user id.
   * @param array $sections
   *   An array of section ids to assign to this user.
   */
  public function removeUser($user_id, $sections = array());

  /**
   * Reomves a set of sections to a role.
   *
   * @param int $role_id
   *   A role id.
   * @param array $sections
   *   An array of section ids to assign to this role.
   */
  public function removeRole($role_id, $sections = array());

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
   * Gets a list of editors assigned to a section.
   *
   * This method does not return editors assigned by role.
   *
   * @param $id
   *   The section id.
   *
   * @return array
   *   An array of user ids.
   */
  public function getEditors($id);

  /**
   * Gets a list of editors who may be assigned to a section.
   *
   * This method does not remove editors already assigned to a section.
   *
   * @param $id
   *   The section id.
   *
   * @return array
   *   An array of user ids.
   */
  public function getPotentialEditors($id);

  /**
   * Gets a list of roles assigned to a section.
   *
   * @param $id
   *   The section id.
   *
   * @return array
   *   An array of role ids.
   */
  public function getRoles($id);

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
   * Gets the editorial sections assigned to a user.
   *
   * @param $uid
   *   An optional user id. If not provided, the active user is returned.
   * @param $add_roles
   *   Whether to add the role-based assignments to the user. Defaults to true.
   *
   * @return
   *   An array of section ids that the user is assigned to.
   */
  public function getUserSections($uid = NULL, $add_roles = TRUE);

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
   * Removes all role assignments.
   *
   * This method should be triggered when changing access schemes. If possible,
   * let the administrator choose to run this.
   */
  public function flushRoles();

  /**
   * Removes all user assignments.
   *
   * This method should be triggered when changing access schemes. If possible,
   * let the administrator choose to run this.
   */
  public function flushUsers();

  /**
   * Resets the internal cache of the tree.
   *
   * Right now, this is a per-request cache until we figure out a long-term
   * caching strategy.
   */
  public function resetTree();

}
