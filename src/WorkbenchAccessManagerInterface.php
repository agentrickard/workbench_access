<?php

namespace Drupal\workbench_access;

/**
 * Defines an interface handling Workbench Access configuration.
 */
interface WorkbenchAccessManagerInterface extends UserSectionStorageInterface, RoleSectionStorageInterface {
  const FIELD_NAME = 'field_workbench_access';

  /**
   * Checks that an entity belongs to a user section or its children.
   *
   * @param array $entity_sections
   *   The section assignments for the entity. An array of section ids.
   * @param array $user_sections
   *   The section assignements for the user. An array of section ids.
   * @param array $tree
   *   Tree to check.
   *
   * return boolean
   */
  public static function checkTree($entity_sections, $user_sections, array $tree);

  /**
   * Returns a flat array of all active section ids.
   *
   * Used to display assignments for admins.
   *
   * @param boolean $root_only
   *   If TRUE, only show the root-level assignments.
   * @param array $tree
   *   Tree to fetch sections from.
   *
   * @return array
   */
  public static function getAllSections($root_only = FALSE, array $tree);

  /**
   * Determines if a user is assigned to all sections.
   *
   * This method checks the permissions and assignments for a user. Someone set
   * as an admin or with access to the top-level sections is assumed to be able
   * to access all sections. We use this logic in query filtering.
   *
   * @param $uid
   *   An optional user id. If not provided, the active user is returned.
   * @param array $tree
   *   Tree.
   *
   * @return boolean
   */
  public function userInAll($uid = NULL, array $tree);

}
