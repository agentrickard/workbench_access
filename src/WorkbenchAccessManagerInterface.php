<?php

/**
 * @file
 * Contains \Drupal\workbench_access\WorkbenchAccessManagerInterface.
 */

namespace Drupal\workbench_access;

use Drupal\Core\Session\AccountInterface;
use Drupal\user\RoleInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\FieldableEntityInterface;

interface WorkbenchAccessManagerInterface {

  public function getSchemes();
  public function getScheme($id);
  public function getActiveScheme();
  public function getActiveTree();
  public function getElement($id);
  public function getDefaultValue();
  public function assignUser(AccountInterface $account, $sections = array());
  public function assignRole(RoleInterface $role, $sections = array());
  public function assignEntity(EntityInterface $entity, $sections = array());
  public function getEditors($id);
  public function getPotentialEditors($id);
  public function getRoles($id);

}
