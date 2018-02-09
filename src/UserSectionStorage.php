<?php

namespace Drupal\workbench_access;

use Drupal\Core\Database\Database;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\workbench_access\Entity\AccessSchemeInterface;

/**
 * Defines a class for storing and retrieving sections assigned to a user.
 */
class UserSectionStorage implements UserSectionStorageInterface {

  /**
   * User storage handler.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $userStorage;

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
    $this->userStorage = $entityTypeManager->getStorage('user');
    $this->currentUser = $currentUser;
    $this->entityTypeManager = $entityTypeManager;
    $this->roleSectionStorage = $role_section_storage;
  }

  /**
   * \Drupal\Core\Entity\EntityStorageInterface
   */
  protected function sectionStorage() {
    // The entity build process takes place too early in the call stack and we
    // have test fails if we add this to the __construct().
    return $this->entityTypeManager->getStorage('section_association');
  }

  /**
   * {@inheritdoc}
   *
   * @TODO: refactor.
   */
  public function getUserSections(AccessSchemeInterface $scheme, $uid = NULL, $add_roles = TRUE) {
    // Get the information from the account.
    if (is_null($uid)) {
      $uid = $this->currentUser->id();
    }
    if (!isset($this->userSectionCache[$scheme->id()][$uid])) {
      $user_sections = $this->loadUserSections($scheme, $uid);
      // Merge in role data.
      if ($add_roles) {
        // In some tests, role data is loading from stale cache,
        $this->userStorage->resetCache([$uid]);
        $user_sections = array_merge($user_sections, $this->roleSectionStorage->getRoleSections($scheme, $this->userStorage->load($uid)));
      }
      $this->userSectionCache[$scheme->id()][$uid] = $user_sections;
    }
    return $this->userSectionCache[$scheme->id()][$uid];
  }

  /**
   * {@inheritdoc}
   */
  public function loadUserSections(AccessSchemeInterface $scheme, $user_id) {
    $query = $this->sectionStorage()->getAggregateQuery()
      ->condition('access_scheme', $scheme->id())
      ->condition('user_id', $user_id)
      ->groupBy('section_id')->execute();
    $list = array_column($query, 'section_id');
    return $list;
  }

  /**
   * {@inheritdoc}
   *
   * @TODO: refactor.
   */
  public function addUser(AccessSchemeInterface $scheme, $user_id, array $sections = []) {
    foreach ($sections as $id) {
      // @TODO: This is tortured logic and probably much easier to handle.
      if ($section_association = $this->sectionStorage()->loadSection($scheme->id(), $id)) {
        $mew_values = [];
        if ($values = $section_association->get('user_id')) {
          foreach ($values as $delta => $value) {
            $target = $value->getValue();
            $new_values[] = $target['target_id'];
          }
          $new_values[] = $user_id;
          $section_association->set('user_id', array_unique($new_values));
        }
        else {
          $section_association->set('user_id', [$user_id]);
        }
        $section_association->setNewRevision();
      }
      else {
        $values = [
          'access_scheme' => $scheme->id(),
          'section_id' => $id,
          'user_id' => [$user_id],
        ];
        $new_values[] = $user_id;
        $section_association = $this->sectionStorage()->create($values);
      }
      $section_association->save();
      $this->resetCache($scheme, $user_id);
      // Return the user object.
      return $this->userStorage->load($user_id);
    }
  }

  /**
   * {@inheritdoc}
   *
   * @TODO: refactor.
   */
  public function removeUser(AccessSchemeInterface $scheme, $user_id, array $sections = []) {
    foreach ($sections as $id) {
      // @TODO: This is tortured logic and probably much easier to handle.
      if ($section_association = $this->sectionStorage()->loadSection($scheme->id(), $id)) {
        $new_values = [];
        if ($values = $section_association->get('user_id')) {
          foreach ($values as $delta => $value) {
            $target = $value->getValue();
            if ($target['target_id'] != $user_id) {
              $new_values[] = $target['target_id'];
            }
          }
          $section_association->set('user_id', array_unique($new_values));
        }
        $section_association->save();
      }
    }
    $this->resetCache($scheme, $user_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getEditors(AccessSchemeInterface $scheme, $id) {
    $query = $this->sectionStorage()->getAggregateQuery()
      ->condition('access_scheme', $scheme->id())
      ->condition('section_id', $id)
      ->groupBy('user_id.target_id')->execute();
    $list = array_column($query, 'user_id_target_id');
    // $list may return an array with a NULL element, which is not 'empty.'
    if (current($list)) {
      return $this->filterByPermission($list);
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getPotentialEditors($id) {
    $query = $this->userStorage->getQuery();
    $users = $query
      ->condition('status', 1)
      ->sort('name')
      ->execute();
    return $this->filterByPermission($users);
  }

  /**
   * {@inheritdoc}
   */
  public function getPotentialEditorsRoles($id) {
    return $this->roleSectionStorage->getPotentialRolesFiltered($id);
  }

  /**
   * {@inheritdoc}
   */
  protected function filterByPermission($users = []) {
    $list = [];
    if (!empty($users)) {
      $entities = $this->userStorage->loadMultiple($users);
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

}
