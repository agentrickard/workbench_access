<?php

/**
 * @file
 * API documentation file for Workbench Access.
 *
 * Note that Workbench Access inplements hook_hook_info().
 * You module may place its hooks inside a file named
 * module.workbench_access.inc for auto-loading by Drupal.
 *
 * Workbench Access uses a pluggable system for managing
 * its access control hierarchies. By default, menu and taxonomy
 * core modules are supported. Other modules may add
 * support by registering with this hook and providing the
 * required data.
 *
 * Required hooks include:
 *  hook_workbench_access_info()
 *  hook_workbench_access_tree()
 *  hook_workbench_access_load()
 *  hook_workbench_access_configuration()
 *
 */

/**
 * Defines your implementation for Workbench Access.
 *
 * This hook declares your module to Workbench Access.
 *
 * @return
 *  An array keyed by the name of your access scheme. (This is generally
 *  the module name.)
 *
 *  Array elements:
 *
 *  - 'access_scheme'
 *    The name of the scheme, generally the name of your module.
 *  - 'name'
 *    The human-readable name of the scheme, wrapped in t().
 *  - 'access_type'
 *    The module that defines core hooks for your access scheme. This can
 *    be different from the access_scheme and use one provided by another
 *    module.
 *  - 'access_type_id'
 *    An array of keys that define the default access items in your structure.
 *    This should mirror a variable_get() from an array of checkboxes. The
 *    variable should be named 'workbench_access_SCHEME'.
 *  - 'description'
 *    A human-readable description of the access control scheme.
 *  - 'configuration'
 *    The configuration callback function. (Will default to
 *    hook_workbench_access_configuration) if not supplied.
 *
 *  The remainder of the elements are used with Views to provide proper query
 *  execution.
 */
function hook_workbench_access_info() {
  return array(
    'menu' => array(
      'access_scheme' => 'menu',
      'name' => t('Menu'),
      'access_type' => 'menu',
      'access_type_id' => array_filter(variable_get('workbench_access_menu', array('main-menu'))),
      'description' => t('Uses a menu for assigning hierarchical access control'),
      'configuration' => 'menu_workbench_access_configuration',
      'node_table' => 'workbench_access_node',
      'query_field' => 'access_id',
      'field_table' => 'workbench_access_node',
      'name_field' => 'name',
      'adjust_join' => array(
        'menu_links' => array(
          'original_table' => 'menu_links',
          'original_field' => 'mlid',
          'new_table' => 'workbench_access_node',
          'new_field' => 'access_id',
        ),
      ),
      'sort' => array(
        array(
          'table' => 'menu_links',
          'field' => 'plid',
        ),
        array(
          'table' => 'menu_links',
          'field' => 'weight',
          'order' => 'ASC',
        ),
      ),
    ),
  );
}
 *  hook_workbench_access_save()
 *  hook_workbench_access_delete()
 *  hook_workbench_access_save_user()
 *  hook_workbench_access_delete_user()
