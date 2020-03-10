<?php

/**
 * Alter the list of entity types protected by each access scheme.
 *
 * In this alter hook, register the entity type that your access scheme controls
 * plus any parent or child types that rely on it.
 *
 * For instance, the menu plugin declares 'menu' and 'menu_link_content' as its
 * watched entity types. This registry will not allow a menu to be deleted if
 * one of its links is used for access control.
 *
 * @see workbench_access_protect_list()
 *
 * @param array $list
 *  An array representing the entity types registered to a scheme, keyed by
 *  scheme.
 */
function hook_workbench_access_protect_list_alter(&$list) {
  // Our example module uses node types as the access scheme. They have no
  // parents.
  $list['node_type'] => ['node_type'];
}
