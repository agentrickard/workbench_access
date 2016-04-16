<?php
/**
 * @file
 * Contains \Drupal\workbench_access\WorkbenchAccessMenuStorage.
 */

namespace Drupal\workbench_access;

use Drupal\Core\Menu\MenuTreeStorage;
use Drupal\Core\Menu\MenuTreeStorageInterface;

/**
 * Overrides the default menu tree storage so we can access private data.
 */

class WorkbenchAccessMenuStorage extends MenuTreeStorage implements MenuTreeStorageInterface {

  /**
   * List of plugin definition fields.
   *
   * @todo Decide how to keep these field definitions in sync.
   *   https://www.drupal.org/node/2302085
   *
   * @see \Drupal\Core\Menu\MenuLinkManager::$defaults
   *
   * @var array
   */
  protected $definitionFields = array(
    'menu_name',
    'mlid',
    'route_name',
    'route_parameters',
    'url',
    'title',
    'description',
    'parent',
    'weight',
    'options',
    'expanded',
    'enabled',
    'provider',
    'metadata',
    'class',
    'form_class',
    'id',
  );

}
