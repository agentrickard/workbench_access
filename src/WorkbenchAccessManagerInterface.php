<?php

/**
 * @file
 * Contains \Drupal\workbench_access\WorkbenchAccessManagerInterface.
 */

namespace Drupal\workbench_access;

interface WorkbenchAccessManagerInterface {

  public function getSchemes();
  public function getScheme($id);
  public function getActiveScheme();
  public function getActiveTree();
  public function getElement($id);
  public static function getDefaultValue();
  public function addUser($user_id, $sections = array());
  public function addRole($role_id, $sections = array());
  public function addEntity($entity_id, $entity_type, $sections = array());
  public function removeUser($user_id, $sections = array());
  public function removeRole($role_id, $sections = array());
  public function removeEntity($entity_id, $entity_type, $sections = array());
  public function getEditors($id);
  public function getPotentialEditors($id);
  public function getRoles($id);
  public function checkTree($entity_sections, $user_sections);

}
