<?php

/**
 * @file Feeds mappers for Workbench Access.
 */

/**
 * Implements hook_feeds_processor_targets_alter().
 */
function workbench_access_feeds_processor_targets_alter(&$targets, $entity_type, $bundle_name){
  if ($entity_type == 'node' && variable_get('workbench_access_node_type_' . $bundle_name, 0)) {
    $targets['workbench_access'] = array(
      'name' => t('Workbench access section'),
      'callback' => 'workbench_access_feeds_set_target',
      'description' => t('The node access section. Both name and id should work'),
    );
  }
}

/**
 * Feeds target callback for setting the current workbench state.
 */
function workbench_access_feeds_set_target($source, $entity, $target, $value) {
  $tree = workbench_access_get_active_tree();

  if (empty($tree['active'])) {
    return;
  }

  $sections = array();
  $current_section = FALSE;

  // In some cases $value is an array. We need only the first value.
  if (is_array($value)) {
    $value = $value[0];
  }
  $value = trim($value);

  foreach ($tree['active'] as $id => $data) {
    $sections[$id] = $tree['tree'][$id]['name'];
  }

  if (isset($sections[$value])) {
    $current_section = $value;
  }
  else {
    foreach ($sections as $id => $name) {
      if ($name == trim($value)) {
        $current_section = $id;
        break;
      }
    }
  }

  // Only set a new current state if we have a valid new state.
  if ($current_section !== FALSE) {
    $entity->workbench_access = $current_section;
  }
}
