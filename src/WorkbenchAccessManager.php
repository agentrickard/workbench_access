<?php

/**
 * @file
 * Contains \Drupal\workbench_access\WorkbenchAccessManager.
 */

namespace Drupal\workbench_access;

use Drupal\workbench_access\WorkbenchAccessManagerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Config\Config;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\RoleInterface;
use Drupal\Core\State\StateInterface;

class WorkbenchAccessManager extends DefaultPluginManager implements WorkbenchAccessManagerInterface {
  use StringTranslationTrait;

  /**
   * The access tree array.
   *
   * @var array
   */
  public $tree;

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
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/AccessControlHierarchy', $namespaces, $module_handler, 'Drupal\workbench_access\AccessControlHierarchyInterface', 'Drupal\workbench_access\Annotation\AccessControlHierarchy');

    $this->alterInfo('workbench_access_info');
    $this->setCacheBackend($cache_backend, 'workbench_access_plugins');
    $this->moduleHandler = $module_handler;
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
    return $this->createInstance($id);
  }

  /**
   * {@inheritdoc}
   */
  public function getActiveScheme() {
    $config = \Drupal::config('workbench_access.settings');
    if ($scheme_id = $config->get('scheme')) {
      return $this->getScheme($scheme_id);
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getActiveTree() {
    if (!isset($this->tree)) {
      $this->tree = $this->getActiveScheme()->getTree();
    }
    return $this->tree;
  }

  /**
   * {@inheritdoc}
   */
  public function resetTree() {
    unset($this->tree);
    $this->getActiveScheme()->resetTree();
  }

  /**
   * {@inheritdoc}
   */
  public function getElement($id) {
    return $this->getActiveScheme()->load($id);
  }

  /**
   * {@inheritdoc}
   */
  public static function getDefaultValue() {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function getUserSections($uid = NULL, $add_roles = TRUE) {
    $user_sections = [];
    // Get the information from the account.
    if (is_null($uid)) {
      $uid = \Drupal::currentUser()->id();
    }
    $user = \Drupal::entityTypeManager()->getStorage('user')->load($uid);
    $sections = $user->get(WORKBENCH_ACCESS_FIELD)->getValue();
    foreach($sections as $data) {
      $user_sections[] = $data['value'];
    }
    // Merge in role data.
    if ($add_roles) {
      $user_sections = array_merge($user_sections, $this->getRoleSections($user));
    }

    return array_unique($user_sections);
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
    $return = FALSE;
    // Get the information from the account.
    if (is_null($uid)) {
      $uid = \Drupal::currentUser()->id();
    }
    $user = \Drupal::entityTypeManager()->getStorage('user')->load($uid);
    if ($user->hasPermission('bypass workbench access')) {
      $return = TRUE;
    }
    else {
      // If the user is assigned to all the top-level sections, treat as admin.
      $user_sections = $this->getUserSections($uid);
      foreach (array_keys($this->getActiveTree()) as $root) {
        $return = TRUE;
        if (empty($user_sections[$root])) {
          $return = FALSE;
        }
      }
    }
    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function addUser($user_id, $sections = array()) {
    $entity = \Drupal::entityManager()->getStorage('user')->load($user_id);
    $values = $this->getUserSections($user_id, FALSE);
    $new = array_merge($values, $sections);
    $entity->set(WORKBENCH_ACCESS_FIELD, $new);
    $entity->save();
  }

  /**
   * {@inheritdoc}
   */
  public function addRole($role_id, $sections = array()) {
    $settings = \Drupal::state()->get(self::WORKBENCH_ACCESS_ROLES_STATE_PREFIX . $role_id, array());
    foreach ($sections as $id) {
      $settings[$id] = 1;
    }
    \Drupal::state()->set(self::WORKBENCH_ACCESS_ROLES_STATE_PREFIX . $role_id, $settings);
  }

  /**
   * {@inheritdoc}
   */
  public function addEntity($entity_id, $entity_type, $sections = array()) {

  }

  /**
   * {@inheritdoc}
   */
  public function removeUser($user_id, $sections = array()) {
    $entity = \Drupal::entityManager()->getStorage('user')->load($user_id);
    $values = $this->getUserSections($user_id, FALSE);
    $new = array_flip($values);
    foreach ($sections as $id) {
      unset($new[$id]);
    }
    $entity->set(WORKBENCH_ACCESS_FIELD, array_keys($new));
    $entity->save();
  }

  /**
   * {@inheritdoc}
   */
  public function removeRole($role_id, $sections = array()) {
    $settings = \Drupal::state()->get(self::WORKBENCH_ACCESS_ROLES_STATE_PREFIX . $role_id, array());
    foreach ($sections as $id) {
      if (isset($settings[$id])) {
        unset($settings[$id]);
      }
    }
    \Drupal::state()->set(self::WORKBENCH_ACCESS_ROLES_STATE_PREFIX . $role_id, $settings);
  }

  /**
   * {@inheritdoc}
   */
  public function removeEntity($entity_id, $entity_type, $sections = array()) {

  }

  /**
   * {@inheritdoc}
   */
  public function getEditors($id) {
    $users = \Drupal::entityQuery('user')
      ->condition(WORKBENCH_ACCESS_FIELD, $id)
      ->condition('status', 1)
      ->sort('name')
      ->execute();
    return $this->filterByPermission($users);
  }

  /**
   * {@inheritdoc}
   */
  public function getPotentialEditors($id) {
    $query = \Drupal::entityQuery('user');
    // For right now, we just show all possible users. If we switch to using
    // an autocomplete form, then we may change back to the filtered query.
    /*
    $query->condition($query->orConditionGroup()
        ->condition(WORKBENCH_ACCESS_FIELD, $id, '<>')
        ->condition(WORKBENCH_ACCESS_FIELD, NULL, 'IS NULL'))
      ->condition('status', 1);
    $users = $query->execute();
    */
    $users = \Drupal::entityQuery('user')
      ->condition('status', 1)
      ->sort('name')
      ->execute();
    return $this->filterByPermission($users);
  }

  /**
   * {@inheritdoc}
   */
  private function filterByPermission($users = array()) {
    $list = [];
    $entities = \Drupal::entityManager()->getStorage('user')->loadMultiple($users);
    foreach ($entities as $account) {
      if ($account->hasPermission('use workbench access')) {
        $list[$account->id()] = $account->label();
      }
    }
    return $list;
  }

  /**
   * {@inheritdoc}
   */
  public function getRoles($id) {
    $list = [];
    $roles = \Drupal::entityManager()->getStorage('user_role')->loadMultiple();
    foreach ($roles as $rid => $role) {
      $settings = \Drupal::state()->get(self::WORKBENCH_ACCESS_ROLES_STATE_PREFIX . $rid, array());
      if (!empty($settings[$id])) {
        $list[$rid] = $role->label();
      }
    }
    return $list;
  }

  /**
   * {@inheritdoc}
   */
  public function getPotentialRoles($id) {
    $list = [];
    $roles = \Drupal::entityManager()->getStorage('user_role')->loadMultiple();
    foreach ($roles as $rid => $role) {
      $list[$rid] = $role->label();
    }
    return $list;
  }

  /**
   * {@inheritdoc}
   */
  public function getRoleSections(AccountInterface $account) {
    $sections = [];
    foreach ($account->getRoles() as $rid) {
      $settings = \Drupal::state()->get(self::WORKBENCH_ACCESS_ROLES_STATE_PREFIX . $rid, array());
      $sections = array_merge($sections, array_keys($settings));
    }
    return $sections;
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
  public function flushRoles() {
    $roles = \Drupal::entityManager()->getStorage('user_role')->loadMultiple();
    foreach ($roles as $rid => $role) {
      \Drupal::state()->delete(self::WORKBENCH_ACCESS_ROLES_STATE_PREFIX . $rid);
    }
    // @TODO clear cache?
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
      $database->truncate('user__' . WORKBENCH_ACCESS_FIELD)->execute();
    }
    catch (Exception $e) {
      drupal_set_message($this->t('Failed to delete user assignments.'));
    }
    // @TODO clear cache?
  }

  /**
   * @inheritdoc
   */
  public function flushFields() {
    // Flush the field settings.
    $config = \Drupal::configFactory()->getEditable('workbench_access.settings');
    $node_types = \Drupal::entityTypeManager()->getStorage('node_type')->loadMultiple();
    foreach ($node_types as $id => $type) {
      $type->setThirdPartySetting('workbench_access', 'workbench_access_status', 0);
      $type->save();
      $fields['node'][$id] = '';
    }
    $config->set('fields', $fields);
    $config->save();
    drupal_set_message($this->t('Field settings reset.'));
  }

}
