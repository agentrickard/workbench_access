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

    foreach ($definitions as $info) {
      if ($this->moduleHandler->moduleExists($info['module'])) {
        $schemes[$info['id']] = $info['label']->render();
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
    $scheme_id = $config->get('scheme');
    $this->manager = \Drupal::getContainer()->get('plugin.manager.workbench_access.scheme');
    return $this->manager->getScheme($scheme_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getActiveTree() {
    return $this->getActiveScheme()->getTree();
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
  public function addUser($user_id, $sections = array()) {
    $entity = \Drupal::entityManager()->getStorage('user')->load($user_id);
    $values = $entity->get(WORKBENCH_ACCESS_FIELD);
    if ($values->isEmpty()) {
      $new = $sections;
    }
    else {
      $new = array_keys($old) + $sections;
    }
    $entity->set(WORKBENCH_ACCESS_FIELD, $new);
    $entity->save();
  }

  /**
   * {@inheritdoc}
   */
  public function addRole($role_id, $sections = array()) {
    $settings = \Drupal::state()->get('workbench_access_roles_' . $role_id, array());
    foreach ($sections as $id) {
      $settings[$id] = 1;
    }
    \Drupal::state()->set('workbench_access_roles_' . $role_id, $settings);
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
    $values = $entity->get(WORKBENCH_ACCESS_FIELD);
    $new = array_keys($values);
    foreach ($sections as $id) {
      unset($new[$id]);
    }
    $entity->set(WORKBENCH_ACCESS_FIELD, $new);
    $entity->save();
  }

  /**
   * {@inheritdoc}
   */
  public function removeRole($role_id, $sections = array()) {
    $settings = \Drupal::state()->get('workbench_access_roles_' . $role_id, array());
    foreach ($sections as $id) {
      if (isset($settings[$id])) {
        unset($settings[$id]);
      }
    }
    \Drupal::state()->set('workbench_access_roles_' . $role_id, $settings);
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
      $settings = \Drupal::state()->get('workbench_access_roles_' . $rid, array());
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
      $settings = \Drupal::state()->get('workbench_access_roles_' . $rid, array());
      $sections += array_keys($settings);
    }
    return $sections;
  }

  /**
   * {@inheritdoc}
   */
  public function checkTree($entity_sections, $user_sections) {
    $tree = $this->getActiveTree();
    $list = array_flip($user_sections);
    foreach ($entity_sections as $field) {
      $section = $field['target_id'];
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
        $parents = array_flip($info[$section]['parents']);
        // Check for parents.
        foreach ($list as $uid => $data) {
          if (isset($parents[$uid])) {
            return TRUE;
          }
        }
      }
    }
    return FALSE;
  }

}
