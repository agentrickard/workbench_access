<?php

namespace Drupal\workbench_access;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;

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
   * @param \Drupal\workbench_access\RoleSectionStorageInterface $roleSectionStorage
   *   Role section storage.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, AccountInterface $currentUser, RoleSectionStorageInterface $roleSectionStorage) {
    $this->userStorage = $entityTypeManager->getStorage('user');
    $this->currentUser = $currentUser;
    $this->roleSectionStorage = $roleSectionStorage;
  }

  /**
   * {@inheritdoc}
   */
  public function getUserSections($uid = NULL, $add_roles = TRUE) {
    // Get the information from the account.
    if (is_null($uid)) {
      $uid = $this->currentUser->id();
    }
    if (!isset($this->userSectionCache[$uid])) {
      $user_sections = [];
      $user = $this->userStorage->load($uid);
      $sections = $user->get(WorkbenchAccessManagerInterface::FIELD_NAME)->getValue();
      foreach ($sections as $data) {
        $user_sections[] = $data['value'];
      }
      // Merge in role data.
      if ($add_roles) {
        $user_sections = array_merge($user_sections, $this->roleSectionStorage->getRoleSections($user));
      }

      $this->userSectionCache[$uid] = array_unique($user_sections);
    }
    return $this->userSectionCache[$uid];

  }


  /**
   * {@inheritdoc}
   */
  public function addUser($user_id, $sections = array()) {
    $entity = $this->userStorage->load($user_id);
    $values = $this->getUserSections($user_id, FALSE);
    $new = array_merge($values, $sections);
    $entity->set(WorkbenchAccessManagerInterface::FIELD_NAME, $new);
    $entity->save();
  }

  /**
   * {@inheritdoc}
   */
  public function removeUser($user_id, $sections = array()) {
    $entity = $this->userStorage->load($user_id);
    $values = $this->getUserSections($user_id, FALSE);
    $new = array_flip($values);
    foreach ($sections as $id) {
      unset($new[$id]);
    }
    $entity->set(WorkbenchAccessManagerInterface::FIELD_NAME, array_keys($new));
    $entity->save();
  }

  /**
   * {@inheritdoc}
   */
  public function getEditors($id) {
    $users = $this->userStorage->getQuery()
      ->condition(WorkbenchAccessManagerInterface::FIELD_NAME, $id)
      ->condition('status', 1)
      ->sort('name')
      ->execute();
    return $this->filterByPermission($users);
  }

  /**
   * {@inheritdoc}
   */
  public function getPotentialEditors($id) {
    $query = $this->userStorage->getQuery();
    // For right now, we just show all possible users. If we switch to using
    // an autocomplete form, then we may change back to the filtered query.
    /*
    $query->condition($query->orConditionGroup()
        ->condition(WorkbenchAccessManagerInterface::FIELD_NAME, $id, '<>')
        ->condition(WorkbenchAccessManagerInterface::FIELD_NAME, NULL, 'IS NULL'))
      ->condition('status', 1);
    $users = $query->execute();
    */
    $users = $query
      ->condition('status', 1)
      ->sort('name')
      ->execute();
    return $this->filterByPermission($users);
  }

  /**
   * @inheritdoc
   */
  public function flushUsers() {
    // We might want to use purgeFieldData() or similar for this, but the data
    // is currently not revisioned, so a simple table flush will do. Wrap the
    // statement in a try/catch just in case it isn't portable.
    try {
      $database = \Drupal::getContainer()->get('database');
      $database->truncate('user__' . WorkbenchAccessManagerInterface::FIELD_NAME)->execute();
    }
    catch (\Exception $e) {
      // @todo return FALSE instead here, let UI handle it.
      drupal_set_message($this->t('Failed to delete user assignments.'));
    }
    // @TODO clear cache?
  }

  /**
   * {@inheritdoc}
   */
  protected function filterByPermission($users = array()) {
    $list = [];
    $entities = $this->userStorage->loadMultiple($users);
    foreach ($entities as $account) {
      if ($account->hasPermission('use workbench access')) {
        $list[$account->id()] = $account->label();
      }
    }
    return $list;
  }

}
