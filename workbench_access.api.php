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

 *
function hook_workbench_access_info() {

}

 *  hook_workbench_access_save()
 *  hook_workbench_access_delete()
 *  hook_workbench_access_save_user()
 *  hook_workbench_access_delete_user()
