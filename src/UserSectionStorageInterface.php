<?php

namespace Drupal\workbench_access;

use Drupal\workbench_access\Entity\AccessSchemeInterface;

/**
 * Defines an interface for storing and retrieving sections for a user.
 */
interface UserSectionStorageInterface {

  /**
   * Adds a set of sections to a user.
   *
   * @param \Drupal\workbench_access\Entity\AccessSchemeInterface $scheme
   *   Access scheme.
   * @param int $user_id
   *   A user id.
   * @param array $sections
   *   An array of section ids to assign to this user.
   */
  public function addUser(AccessSchemeInterface $scheme, $user_id, $sections = []);

  /**
   * Removes a set of sections to a user.
   *
   * @param \Drupal\workbench_access\Entity\AccessSchemeInterface $scheme
   *   Access scheme.
   * @param int $user_id
   *   A user id.
   * @param array $sections
   *   An array of section ids to assign to this user.
   */
  public function removeUser(AccessSchemeInterface $scheme, $user_id, $sections = []);

  /**
   * Gets a list of editors assigned to a section.
   *
   * This method does not return editors assigned by role.
   *
   * @param \Drupal\workbench_access\Entity\AccessSchemeInterface $scheme
   *   Access scheme.
   * @param $id
   *   The section id.
   *
   * @return array
   *   An array of user ids.
   */
  public function getEditors(AccessSchemeInterface $scheme, $id);

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
   * Gets a list of editors roles who may be assigned to a section.
   *
   * @param $id
   *   The section id.
   *
   * @return array
   *   An array of role ids.
   */
  public function getPotentialEditorsRoles($id);

  /**
   * Gets the editorial sections assigned to a user.
   *
   * @param \Drupal\workbench_access\Entity\AccessSchemeInterface $scheme
   *   Access scheme.
   * @param $uid
   *   An optional user id. If not provided, the active user is returned.
   * @param $add_roles
   *   Whether to add the role-based assignments to the user. Defaults to true.
   *
   * @return
   *   An array of section ids that the user is assigned to.
   */
  public function getUserSections(AccessSchemeInterface $scheme, $uid = NULL, $add_roles = TRUE);

  /**
   * Removes all user assignments.
   *
   * This method should be triggered when changing access schemes. If possible,
   * let the administrator choose to run this.
   */
  public function flushUsers();

}
