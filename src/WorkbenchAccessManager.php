<?php

namespace Drupal\workbench_access;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines a class for interacting with content and fields.
 */
class WorkbenchAccessManager extends DefaultPluginManager implements WorkbenchAccessManagerInterface {
  use StringTranslationTrait;

  /**
   * Static cache of user sections keyed by user ID.
   *
   * @var array
   */
  protected $userSectionCache = [];

  /**
   * The access tree array.
   *
   * @var array
   */
  public $tree;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * User section storage.
   *
   * @var \Drupal\workbench_access\UserSectionStorageInterface
   */
  protected $userSectionStorage;

  /**
   * Role section storage.
   *
   * @var \Drupal\workbench_access\RoleSectionStorageInterface
   */
  protected $roleSectionStorage;

  /**
   * Module config.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a new WorkbenchAccessManager.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   * @param \Drupal\workbench_access\UserSectionStorageInterface $userSectionStorage
   *   User section storage.
   * @param \Drupal\workbench_access\RoleSectionStorageInterface $roleSectionStorage
   *   Role section storage.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Config factory.
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   Current user.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, EntityTypeManagerInterface $entityTypeManager, UserSectionStorageInterface $userSectionStorage, RoleSectionStorageInterface $roleSectionStorage, ConfigFactoryInterface $configFactory, AccountInterface $currentUser) {
    parent::__construct('Plugin/AccessControlHierarchy', $namespaces, $module_handler, 'Drupal\workbench_access\AccessControlHierarchyInterface', 'Drupal\workbench_access\Annotation\AccessControlHierarchy');

    $this->alterInfo('workbench_access_info');
    $this->setCacheBackend($cache_backend, 'workbench_access_plugins');
    $this->moduleHandler = $module_handler;
    $this->namespaces = $namespaces;
    $this->userSectionStorage = $userSectionStorage;
    $this->configFactory = $configFactory;
    $this->currentUser = $currentUser;
    $this->entityTypeManager = $entityTypeManager;
    $this->roleSectionStorage = $roleSectionStorage;
  }

  /**
   * {@inheritdoc}
   */
  public function getSchemes() {
    $schemes = array();
    $definitions = $this->getDefinitions();

    foreach ($definitions as $id => $info) {
      if ($this->moduleHandler->moduleExists($info['module'])) {
        $schemes[$id] = (string) $info['label'];
      }
    }

    return $schemes;
  }

  /**
   * {@inheritdoc}
   */
  public function getScheme($id) {
    try {
      return $this->createInstance($id);
    }
    catch (PluginNotFoundException $e) {
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getActiveScheme() {
    if ($scheme_id = $this->configFactory->get('workbench_access.settings')->get('scheme')) {
      return $this->getScheme($scheme_id);
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getActiveTree() {
    if (!isset($this->tree)) {
      if ($scheme = $this->getActiveScheme()) {
        $this->tree = $scheme->getTree();
      }
    }
    return $this->tree;
  }

  /**
   * {@inheritdoc}
   */
  public function resetTree() {
    unset($this->tree);
    if ($scheme = $this->getActiveScheme()) {
      $scheme->resetTree();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getElement($id) {
    if ($scheme = $this->getActiveScheme()) {
      return $scheme->load($id);
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public static function getDefaultValue() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function addEntity($entity_id, $entity_type, $sections = array()) {

  }

  /**
   * {@inheritdoc}
   */
  public function removeEntity($entity_id, $entity_type, $sections = array()) {

  }

  /**
   * {@inheritdoc}
   */
  public function checkTree($entity_sections, $user_sections) {
    $tree = $this->getActiveTree();
    $list = array_flip($user_sections);
    foreach ($entity_sections as $section) {
      // Simple check first: is there an exact match?
      if (isset($list[$section])) {
        return TRUE;
      }
      // Check for section on the tree.
      foreach ($tree as $id => $info) {
        if (isset($list[$section]) && isset($info[$section])) {
          return TRUE;
        }
        // Recursive check for parents.
        if (!empty($info[$section]['parents'])) {
          $parents = array_flip($info[$section]['parents']);
          // Check for parents.
          foreach ($list as $uid => $data) {
            if (isset($parents[$uid])) {
              return TRUE;
            }
          }
        }
      }
    }
    return FALSE;
  }

  /**
   * @inheritdoc
   */
  public function flushFields() {
    // Flush the field settings.
    $node_types = $this->entityTypeManager->getStorage('node_type')->loadMultiple();
    foreach ($node_types as $id => $type) {
      $type->setThirdPartySetting('workbench_access', 'workbench_access_status', 0);
      $type->save();
      $fields['node'][$id] = '';
    }
    $this->configFactory->getEditable('workbench_access.settings')
      ->set('fields', $fields)
      ->save();
    drupal_set_message($this->t('Field settings reset.'));
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated To be removed before 8.x-1.0 - use
   *   \Drupal\workbench_access\RoleSectionStorage::addRole() instead
   */
  public function addRole($role_id, $sections = array()) {
    trigger_error(__CLASS__ . '::' . __METHOD__ . ' is deprecated, please use \Drupal\workbench_access\RoleSectionStorage::' . __METHOD__ . ' instead', E_USER_DEPRECATED);
    $this->roleSectionStorage->addRole($role_id, $sections);
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated To be removed before 8.x-1.0 - use
   *   \Drupal\workbench_access\RoleSectionStorage::removeRole() instead
   */
  public function removeRole($role_id, $sections = array()) {
    trigger_error(__CLASS__ . '::' . __METHOD__ . ' is deprecated, please use \Drupal\workbench_access\RoleSectionStorage::' . __METHOD__ . ' instead', E_USER_DEPRECATED);
    $this->roleSectionStorage->removeRole($role_id, $sections);
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated To be removed before 8.x-1.0 - use
   *   \Drupal\workbench_access\RoleSectionStorage::getRoles() instead
   */
  public function getRoles($id) {
    trigger_error(__CLASS__ . '::' . __METHOD__ . ' is deprecated, please use \Drupal\workbench_access\RoleSectionStorage::' . __METHOD__ . ' instead', E_USER_DEPRECATED);
    $this->roleSectionStorage->getRoles($id);
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated To be removed before 8.x-1.0 - use
   *   \Drupal\workbench_access\RoleSectionStorage::flushRoles() instead
   */
  public function flushRoles() {
    trigger_error(__CLASS__ . '::' . __METHOD__ . ' is deprecated, please use \Drupal\workbench_access\RoleSectionStorage::' . __METHOD__ . ' instead', E_USER_DEPRECATED);
    $this->roleSectionStorage->flushRoles();
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated To be removed before 8.x-1.0 - use
   *   \Drupal\workbench_access\UserSectionStorage::addUser() instead
   */
  public function addUser($user_id, $sections = array()) {
    trigger_error(__CLASS__ . '::' . __METHOD__ . ' is deprecated, please use \Drupal\workbench_access\UserSectionStorage::' . __METHOD__ . ' instead', E_USER_DEPRECATED);
    $this->userSectionStorage->addUser($user_id, $sections);
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated To be removed before 8.x-1.0 - use
   *   \Drupal\workbench_access\UserSectionStorage::removeUser() instead
   */
  public function removeUser($user_id, $sections = array()) {
    trigger_error(__CLASS__ . '::' . __METHOD__ . ' is deprecated, please use \Drupal\workbench_access\UserSectionStorage::' . __METHOD__ . ' instead', E_USER_DEPRECATED);
    $this->userSectionStorage->removeUser($user_id, $sections);
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated To be removed before 8.x-1.0 - use
   *   \Drupal\workbench_access\UserSectionStorage::getEditors() instead
   */
  public function getEditors($id) {
    trigger_error(__CLASS__ . '::' . __METHOD__ . ' is deprecated, please use \Drupal\workbench_access\UserSectionStorage::' . __METHOD__ . ' instead', E_USER_DEPRECATED);
    return $this->userSectionStorage->getEditors($id);
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated To be removed before 8.x-1.0 - use
   *   \Drupal\workbench_access\UserSectionStorage::getPotentialEditors() instead
   */
  public function getPotentialEditors($id) {
    trigger_error(__CLASS__ . '::' . __METHOD__ . ' is deprecated, please use \Drupal\workbench_access\UserSectionStorage::' . __METHOD__ . ' instead', E_USER_DEPRECATED);
    return $this->userSectionStorage->getPotentialEditors($id);
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated To be removed before 8.x-1.0 - use
   *   \Drupal\workbench_access\UserSectionStorage::getUserSections() instead
   */
  public function getUserSections($uid = NULL, $add_roles = TRUE) {
    trigger_error(__CLASS__ . '::' . __METHOD__ . ' is deprecated, please use \Drupal\workbench_access\UserSectionStorage::' . __METHOD__ . ' instead', E_USER_DEPRECATED);
    return $this->userSectionStorage->getUserSections($uid, $add_roles);
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated To be removed before 8.x-1.0 - use
   *   \Drupal\workbench_access\UserSectionStorage::flushUsers() instead
   */
  public function flushUsers() {
    trigger_error(__CLASS__ . '::' . __METHOD__ . ' is deprecated, please use \Drupal\workbench_access\UserSectionStorage::' . __METHOD__ . ' instead', E_USER_DEPRECATED);
    $this->userSectionStorage->flushUsers();
  }

  /**
   * {@inheritdoc}
   */
  public function getAllSections($root_only = FALSE) {
    $sections = [];
    foreach ($this->getActiveTree() as $root => $item) {
      if ($root_only) {
        $sections[] = $root;
      }
      else {
        foreach ($item as $id => $data) {
          $sections[] = $id;
        }
      }
    }
    return $sections;
  }

  /**
   * {@inheritdoc}
   */
  public function userInAll($uid = NULL) {
    // Get the information from the account.
    if (is_null($uid)) {
      $uid = $this->currentUser->id();
    }
    $user = $this->entityTypeManager->getStorage('user')->load($uid);
    if ($user->hasPermission('bypass workbench access')) {
      return TRUE;
    }
    else {
      // If the user is assigned to all the top-level sections, treat as admin.
      $user_sections = $this->userSectionStorage->getUserSections($uid);
      foreach (array_keys($this->getActiveTree()) as $root) {
        if (empty($user_sections[$root])) {
          return FALSE;
        }
      }
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated To be removed before 8.x-1.0 - use
   *   \Drupal\workbench_access\RoleSectionStorage::getRoleSections() instead
   */
  public function getRoleSections(AccountInterface $account) {
    trigger_error(__CLASS__ . '::' . __METHOD__ . ' is deprecated, please use \Drupal\workbench_access\RoleSectionStorage::' . __METHOD__ . ' instead', E_USER_DEPRECATED);
    return $this->roleSectionStorage->getRoleSections($account);
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated To be removed before 8.x-1.0 - use
   *   \Drupal\workbench_access\RoleSectionStorage::getPotentialRoles() instead
   */
  public function getPotentialRoles($id) {
    trigger_error(__CLASS__ . '::' . __METHOD__ . ' is deprecated, please use \Drupal\workbench_access\RoleSectionStorage::' . __METHOD__ . ' instead', E_USER_DEPRECATED);
    return $this->roleSectionStorage->getPotentialRoles($id);
  }

}
