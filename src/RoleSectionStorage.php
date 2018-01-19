<?php

namespace Drupal\workbench_access;

use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\State\StateInterface;
use Drupal\workbench_access\Entity\AccessSchemeInterface;

/**
 * Defines a role-section storage that uses the State API.
 */
class RoleSectionStorage implements RoleSectionStorageInterface {

  use DependencySerializationTrait;

  /**
   * Section association storage service.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $sectionStorage;

  /**
   * State.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Role storage.
   *
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
    $this->sectionStorage = $entityTypeManager->getStorage('section_association');
  }

  /**
   * {@inheritdoc}
   */
  public function addRole(AccessSchemeInterface $scheme, $role_id, array $sections = []) {
    $settings = $this->getRoles($scheme, $role_id);
    foreach ($sections as $id) {
      $settings[$id] = 1;
    }
    $this->saveRoleSections($scheme, $role_id, $settings);
  }

  /**
   * {@inheritdoc}
   */
  public function removeRole(AccessSchemeInterface $scheme, $role_id, array $sections = []) {
    $settings = $this->getRoles($scheme, $role_id);
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
      $settings = $this->getRoles($scheme, $rid);
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
    $query = $this->sectionStorage->getAggregateQuery()
      ->condition('section_scheme_id', $scheme->id())
      ->condition('section_id', $id)
      ->groupBy('role_id.target_id')->execute();
    $roles = $this->roleStorage->loadMultiple(array_column($query, 'role_id__target_id'));
    return $roles;
  }

  /**
   * {@inheritdoc}
   */
  public function flushRoles(AccessSchemeInterface $scheme) {
    $roles = $this->roleStorage->loadMultiple();
    foreach ($roles as $rid => $role) {
      $this->deleteRoleSections($scheme, $rid);
    }
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
   *
   * @TODO: refactor.
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
   *
   * @TODO: refactor.
   */
  protected function deleteRoleSections(AccessSchemeInterface $scheme, $rid) {
    return $this->state->delete(self::WORKBENCH_ACCESS_ROLES_STATE_PREFIX . $scheme->id() . '__' . $rid);
  }

}
