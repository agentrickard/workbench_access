<?php

namespace Drupal\workbench_access;

/**
 * Defines an interface for storing and retrieving sections for a user.
 */
interface UserSectionStorageInterface {

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
   * Removes a set of sections to a user.
   *
   * @param int $user_id
   *   A user id.
   * @param array $sections
   *   An array of section ids to assign to this user.
   */
  public function removeUser($user_id, $sections = array());

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
   * Removes all user assignments.
   *
   * This method should be triggered when changing access schemes. If possible,
   * let the administrator choose to run this.
   */
  public function flushUsers();

}
