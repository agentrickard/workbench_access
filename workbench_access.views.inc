<?php

/**
 * @file
 * Provide views data for workbench_access.module.
 *
 * @ingroup views_module_handlers
 */

use Drupal\workbench_access\ViewsData;

/**
 * Implements hook_views_data().
 */
function workbench_access_views_data() {
  return _workbench_access_views_data_object()->getViewsData();
}

/**
 * Creates a ViewsData object to respond to views hooks.
 *
 * @return \Drupal\workbench_access\ViewsData
 *   The workbench access ViewsData object.
 */
function _workbench_access_views_data_object() {
  return new ViewsData(
    \Drupal::service('entity_type.manager'),
    \Drupal::service('workbench_access.user_section_storage'),
    \Drupal::service('workbench_access.role_section_storage')
  );
}
