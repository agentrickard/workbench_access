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

  public function getScheme($id) {
    return $this->createInstance($id);
  }

  public function getActiveScheme() {
    // @TODO: Inject the config service?
  }

  public function getActiveTree() {

  }

}
