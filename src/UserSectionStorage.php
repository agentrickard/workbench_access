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
   * Section association storage service.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $sectionStorage;

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
    $this->sectionStorage = $entityTypeManager->getStorage('section_association');
    $this->currentUser = $currentUser;
    $this->roleSectionStorage = $role_section_storage;
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
      $user = $this->userStorage->load($uid);
      $user_sections = $this->unformatAndFilterSections($scheme, array_column($user->get(WorkbenchAccessManagerInterface::FIELD_NAME)->getValue(), 'value'));
      // Merge in role data.
      if ($add_roles) {
        $user_sections = array_merge($user_sections, $this->roleSectionStorage->getRoleSections($scheme, $user));
      }

      $this->userSectionCache[$scheme->id()][$uid] = array_unique($user_sections);
    }
    return $this->userSectionCache[$scheme->id()][$uid];

  }

  /**
   * {@inheritdoc}
   *
   * @TODO: refactor.
   */
  public function addUser(AccessSchemeInterface $scheme, $user_id, array $sections = []) {
    $entity = $this->userStorage->load($user_id);
    $values = array_column($entity->get(WorkbenchAccessManagerInterface::FIELD_NAME)->getValue(), 'value');
    $new = array_merge($values, $this->formatSections($scheme, $sections));
    $entity->set(WorkbenchAccessManagerInterface::FIELD_NAME, $new);
    $entity->save();
    $this->userSectionCache[$scheme->id()][$user_id] = $this->unformatAndFilterSections($scheme, $new);
  }

  /**
   * {@inheritdoc}
   *
   * @TODO: refactor.
   */
  public function removeUser(AccessSchemeInterface $scheme, $user_id, array $sections = []) {
    $entity = $this->userStorage->load($user_id);
    $values = array_column($entity->get(WorkbenchAccessManagerInterface::FIELD_NAME)->getValue(), 'value');
    $new = array_flip($values);
    $sections = $this->formatSections($scheme, $sections);
    foreach ($sections as $id) {
      unset($new[$id]);
    }
    $entity->set(WorkbenchAccessManagerInterface::FIELD_NAME, array_keys($new));
    $entity->save();
    $this->userSectionCache[$scheme->id()][$user_id] = $this->unformatAndFilterSections($scheme, array_keys($new));
  }

  /**
   * {@inheritdoc}
   */
  public function getEditors(AccessSchemeInterface $scheme, $id) {
    $query = $this->sectionStorage->getAggregateQuery()
      ->condition('access_scheme', $scheme->id())
      ->condition('section_id', $id)
      ->groupBy('user_id.target_id')->execute();
    $users = $this->userStorage->loadMultiple(array_column($query, 'user_id__target_id'));
    return $this->filterByPermission($users);
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
   *
   * @TODO: Refactor.
   */
  public function flushUsers(AccessSchemeInterface $scheme) {
    $users = $this->userStorage->loadMultiple($this->userStorage->getQuery()
      ->condition(WorkbenchAccessManagerInterface::FIELD_NAME, Database::getConnection()
        ->escapeLike(sprintf('%s:', $scheme->id())) . '%', 'LIKE')
      ->sort('name')
      ->execute());
    foreach ($users as $user) {
      $values = array_column($user->get(WorkbenchAccessManagerInterface::FIELD_NAME)->getValue(), 'value');
      $updated_values = array_filter($values, function ($item) use ($scheme) {
        list($scheme_id) = explode(':', $item, 2);
        return $scheme_id !== $scheme->id();
      });
      if ($values !== $updated_values) {
        $user->set(WorkbenchAccessManagerInterface::FIELD_NAME, $updated_values);
        $user->save();
      }
    }
    unset($this->userSectionCache[$scheme->id()]);
  }

  /**
   * {@inheritdoc}
   */
  protected function filterByPermission($users = []) {
    $list = [];
    $entities = $this->userStorage->loadMultiple($users);
    foreach ($entities as $account) {
      if ($account->hasPermission('use workbench access')) {
        $list[$account->id()] = $account->label();
      }
    }
    return $list;
  }

  /**
   * Formats sections in {scheme_id}:{section_id} format.
   *
   * @param \Drupal\workbench_access\Entity\AccessSchemeInterface $scheme
   *   Access scheme.
   * @param array $sections
   *   Sections to format.
   *
   * @return array
   *   Formatted sections.
   */
  protected function formatSections(AccessSchemeInterface $scheme, array $sections) {
    return array_map(function ($section) use ($scheme) {
      return sprintf('%s:%s', $scheme->id(), $section);
    }, $sections);
  }

  /**
   * Unformats sections from {scheme_id}:{section_id} format and filters.
   *
   * @param \Drupal\workbench_access\Entity\AccessSchemeInterface $scheme
   *   Access scheme.
   * @param array $sections
   *   Values to unformat and filter.
   *
   * @return array
   *   Unformatted and filtered sections.
   */
  protected function unformatAndFilterSections(AccessSchemeInterface $scheme, array $sections) {
    return array_reduce($sections, function ($carry, $section) use ($scheme) {
      list($scheme_id, $section_id) = explode(':', $section, 2);
      if ($scheme_id !== $scheme->id()) {
        return $carry;
      }
      $carry[] = $section_id;
      return $carry;
    }, []);
  }

}
