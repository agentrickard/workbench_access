<?php

namespace Drupal\workbench_access;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\State\StateInterface;
use Drupal\workbench_access\Entity\AccessSchemeInterface;

/**
 * Defines a role-section storage that uses the State API.
 */
class RoleSectionStorage implements RoleSectionStorageInterface {

  /**
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $roleStorage;

  /**
   * Constructs a new RoleSectionStorage object.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   State service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   */
  public function __construct(StateInterface $state, EntityTypeManagerInterface $entityTypeManager) {
    $this->state = $state;
    $this->roleStorage = $entityTypeManager->getStorage('user_role');
  }

  /**
   * {@inheritdoc}
   */
  public function addRole(AccessSchemeInterface $scheme, $role_id, $sections = []) {
    $settings = $this->loadRoleSections($scheme, $role_id);
    foreach ($sections as $id) {
      $settings[$id] = 1;
    }
    $this->saveRoleSections($scheme, $role_id, $settings);
  }

  /**
   * {@inheritdoc}
   */
  public function removeRole(AccessSchemeInterface $scheme, $role_id, $sections = []) {
    $settings = $this->loadRoleSections($scheme, $role_id);
    foreach ($sections as $id) {
      if (isset($settings[$id])) {
        unset($settings[$id]);
      }
    }
    $this->saveRoleSections($scheme, $role_id, $settings);
  }

  /**
   * {@inheritdoc}
   */
  public function getRoleSections(AccessSchemeInterface $scheme, AccountInterface $account) {
    $sections = [];
    foreach ($account->getRoles() as $rid) {
      $settings = $this->loadRoleSections($scheme, $rid);
      $sections = array_merge($sections, array_keys($settings));
    }
    return $sections;
  }

  /**
   * {@inheritdoc}
   */
  public function getPotentialRoles($id) {
    $list = [];
    $roles = $this->roleStorage->loadMultiple();
    foreach ($roles as $rid => $role) {
      $list[$rid] = $role->label();
    }
    return $list;
  }

  /**
   * {@inheritdoc}
   */
  public function getPotentialRolesFiltered($id) {
    $list = [];
    $roles = $this->roleStorage->loadMultiple();
    foreach ($roles as $rid => $role) {
      if ($role->hasPermission('use workbench access')) {
        $list[$rid] = $role->label();
      }
    }
    return $list;
  }

  /**
   * {@inheritdoc}
   */
  public function getRoles(AccessSchemeInterface $scheme, $id) {
    $list = [];
    $roles = $this->roleStorage->loadMultiple();
    foreach ($roles as $rid => $role) {
      $settings = $this->loadRoleSections($scheme, $rid);
      if (!empty($settings[$id])) {
        $list[$rid] = $role->label();
      }
    }
    return $list;
  }

  /**
   * @inheritdoc
   */
  public function flushRoles(AccessSchemeInterface $scheme) {
    $roles = $this->roleStorage->loadMultiple();
    foreach ($roles as $rid => $role) {
      $this->deleteRoleSections($scheme, $rid);
    }
    // @TODO clear cache?
  }


  /**
   * Loads the saved role sections for a given role ID.
   *
   * @param \Drupal\workbench_access\Entity\AccessSchemeInterface $scheme
   *   Access scheme.
   * @param string $role_id
   *   The role ID.
   *
   * @return array
   *   Sections for role.
   */
  protected function loadRoleSections(AccessSchemeInterface $scheme, $role_id) {
    return $this->state->get(self::WORKBENCH_ACCESS_ROLES_STATE_PREFIX . $scheme->id() . '__' . $role_id, []);
  }

  /**
   * Saves the role sections for a given role ID.
   *
   * @param \Drupal\workbench_access\Entity\AccessSchemeInterface $scheme
   *   Access scheme.
   * @param string $role_id
   *   The role ID.
   * @param array $settings
   *   Sections for the role.
   */
  protected function saveRoleSections(AccessSchemeInterface $scheme, $role_id, array $settings = []) {
    return $this->state->set(self::WORKBENCH_ACCESS_ROLES_STATE_PREFIX . $scheme->id() . '__' . $role_id, $settings);
  }

  /**
   * Delete the saved sections for this role.
   *
   * @param \Drupal\workbench_access\Entity\AccessSchemeInterface $scheme
   *   Access scheme.
   * @param string $rid
   *   The role ID.
   */
  protected function deleteRoleSections(AccessSchemeInterface $scheme, $rid) {
    return $this->state->delete(self::WORKBENCH_ACCESS_ROLES_STATE_PREFIX . $scheme->id() . '__' . $rid);
  }

}
