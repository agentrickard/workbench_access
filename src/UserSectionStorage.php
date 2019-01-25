<?php

namespace Drupal\workbench_access;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\workbench_access\Entity\AccessSchemeInterface;

/**
 * Defines a class for storing and retrieving sections assigned to a user.
 */
class UserSectionStorage implements UserSectionStorageInterface {

  /**
   * Static cache to prevent recalculation of sections for a user in a request.
   *
   * @var array
   */
  protected $userSectionCache;

  /**
   * Current user service.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Role section storage service.
   *
   * @var \Drupal\workbench_access\RoleSectionStorageInterface
   */
  protected $roleSectionStorage;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new UserSectionStorage object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   Current user.
   * @param \Drupal\workbench_access\RoleSectionStorageInterface $role_section_storage
   *   Role section storage.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, AccountInterface $currentUser, RoleSectionStorageInterface $role_section_storage) {
    $this->currentUser = $currentUser;
    $this->entityTypeManager = $entityTypeManager;
    $this->roleSectionStorage = $role_section_storage;
  }

  /**
   * Gets section storage.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface
   *   Section storage.
   */
  protected function sectionStorage() {
    // The entity build process takes place too early in the call stack and we
    // have test fails if we add this to the __construct().
    return $this->entityTypeManager->getStorage('section_association');
  }

  /**
   * {@inheritdoc}
   */
  public function getUserSections(AccessSchemeInterface $scheme, AccountInterface $account = NULL, $add_roles = TRUE) {
    // Get the information from the account.
    if (!$account) {
      $account = $this->currentUser;
    }
    if (!isset($this->userSectionCache[$scheme->id()][$account->id()])) {
      $user_sections = $this->loadUserSections($scheme, $account);
      // Merge in role data.
      if ($add_roles) {
        $user_sections = array_merge($user_sections, $this->roleSectionStorage->getRoleSections($scheme, $account));
      }
      $this->userSectionCache[$scheme->id()][$account->id()] = $user_sections;
    }
    return $this->userSectionCache[$scheme->id()][$account->id()];
  }

  /**
   * {@inheritdoc}
   */
  protected function loadUserSections(AccessSchemeInterface $scheme, AccountInterface $account) {
    $query = $this->sectionStorage()->getAggregateQuery()
      ->condition('access_scheme', $scheme->id())
      ->condition('user_id', $account->id())
      ->groupBy('section_id')->execute();
    $list = array_column($query, 'section_id');
    return $list;
  }

  /**
   * {@inheritdoc}
   */
  public function addUser(AccessSchemeInterface $scheme, AccountInterface $account, array $sections = []) {
    foreach ($sections as $id) {
      if ($section_association = $this->sectionStorage()->loadSection($scheme->id(), $id)) {
        if ($new_values = $section_association->getCurrentUserIds()) {
          $new_values[] = $account->id();
          $section_association->set('user_id', array_unique($new_values));
        }
        else {
          $section_association->set('user_id', [$account->id()]);
        }
        $section_association->setNewRevision();
      }
      else {
        $values = [
          'access_scheme' => $scheme->id(),
          'section_id' => $id,
          'user_id' => [$account->id()],
        ];
        $new_values[] = $account->id();
        $section_association = $this->sectionStorage()->create($values);
      }
      $section_association->save();
      $this->resetCache($scheme, $account->id());
    }
    // Return the user object.
    return $this->userStorage()->load($account->id());
  }

  /**
   * {@inheritdoc}
   */
  public function removeUser(AccessSchemeInterface $scheme, AccountInterface $account, array $sections = []) {
    foreach ($sections as $id) {
      $new_values = [];
      if ($section_association = $this->sectionStorage()->loadSection($scheme->id(), $id)) {
        if ($values = $section_association->getCurrentUserIds()) {
          foreach ($values as $delta => $value) {
            if ($value != $account->id()) {
              $new_values[] = $value;
            }
          }
          $section_association->set('user_id', array_unique($new_values));
        }
        $section_association->save();
      }
    }
    $this->resetCache($scheme, $account->id());

    // Return the user object.
    return $this->userStorage()->load($account->id());
  }

  /**
   * {@inheritdoc}
   */
  public function getEditors(AccessSchemeInterface $scheme, $id) {
    $query = $this->sectionStorage()->getAggregateQuery()
      ->condition('access_scheme', $scheme->id())
      ->condition('section_id', $id)
      ->groupBy('user_id.target_id')
      ->groupBy('user_id.entity.name');
    $data = $query->execute();
    $list = array_column($data, 'name', 'user_id_target_id');
    // $list may return an array with a NULL element, which is not 'empty.'.
    if (current($list)) {
      return $list;
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getPotentialEditors($id) {
    // Get all role IDs that have the configured permissions.
    $roles = user_role_names(FALSE, 'use workbench access');
    // user_role_names() returns an array with the role IDs as keys, so take
    // the array keys and merge them with previously found role IDs.
    $rids = array_keys($roles);
    $query = $this->userStorage()->getQuery();
    $query->condition('status', 1)
          ->sort('name');
    if (!in_array(AccountInterface::AUTHENTICATED_ROLE, $rids, TRUE)) {
      $query->condition('roles', $rids, 'IN');
    }
    $users = $query->execute();

    return $users;
  }

  /**
   * {@inheritdoc}
   */
  public function getPotentialEditorsRoles($id) {
    return $this->roleSectionStorage->getPotentialRolesFiltered($id);
  }

  /**
   * Filters a user list by permission.
   *
   * Note that with a high number of users, this method causes performance
   * issues. Try to filter at the query level.
   *
   * @param array $users
   *   An array of users keyed by id with names as the value.
   * @return array
   *   An array of users keyed by id with names as the value.
   *
   * @deprecated and will be removed before the 8.x-1.0 stable release.
   *   Instead, you should filter at the query level by using role permissions.
   *
   * @see UserSectionStorage::getPotentialEditors()
   *
   * @link
   *   https://www.drupal.org/project/workbench_access/issues/3025466
   */
  protected function filterByPermission($users = []) {
    @trigger_error('UserSectionStorage::filterByPermission() is deprecated and
      will be removed before the 8.x-1.0 stable release. Instead, you should
      filter at the query level by using role permissions.');
    $list = [];
    if (!empty($users)) {
      $entities = $this->userStorage()->loadMultiple($users);
      foreach ($entities as $account) {
        if ($account->hasPermission('use workbench access')) {
          $list[$account->id()] = $account->label();
        }
      }
    }
    return $list;
  }

  /**
   * Reset the static cache from an external change.
   */
  public function resetCache(AccessSchemeInterface $scheme, $user_id = NULL) {
    if ($user_id && isset($this->userSectionCache[$scheme->id()][$user_id])) {
      unset($this->userSectionCache[$scheme->id()][$user_id]);
    }
    elseif (isset($this->userSectionCache[$scheme->id()])) {
      unset($this->userSectionCache[$scheme->id()]);
    }
  }

  /**
   * Gets user storage handler.
   *
   * The entity build process takes place too early in the call stack so we
   * end up with a stale reference to the user storage handler if we do this in
   * the constructor.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface
   *   User storage.
   */
  protected function userStorage() {
    return $this->entityTypeManager->getStorage('user');
  }

}
