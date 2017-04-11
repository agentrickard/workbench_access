<?php

namespace Drupal\workbench_access;

use Drupal\Core\Session\AccountInterface;

/**
 * Defines an interface for storing and retrieving sections for a role.
 */
interface RoleSectionStorageInterface {

  /**
   * State prefix.
   */
  const WORKBENCH_ACCESS_ROLES_STATE_PREFIX = 'workbench_access_roles_';

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
   * Reomves a set of sections to a role.
   *
   * @param int $role_id
   *   A role id.
   * @param array $sections
   *   An array of section ids to assign to this role.
   */
  public function removeRole($role_id, $sections = array());

  /**
   * Gets a list of potential roles.
   *
   * @param string $id
   *   The section id.
   *
   * @return array
   *   An array of roles keyed by rid with name values.
   */
  public function getPotentialRoles($id);

  /**
   * Gets a list of roles assigned to a section.
   *
   * @param string $id
   *   The section id.
   *
   * @return array
   *   An array of role ids.
   */
  public function getRoles($id);

  /**
   * Removes all role assignments.
   *
   * This method should be triggered when changing access schemes. If possible,
   * let the administrator choose to run this.
   */
  public function flushRoles();

  /**
   * Gets the sections assigned to a user by way of their roles.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account to retrieve sections for by way of their roles.
   *
   * @return array
   *   Array of section IDs.
   */
  public function getRoleSections(AccountInterface $account);

}
