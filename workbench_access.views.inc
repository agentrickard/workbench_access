<?php

/**
 * @file
 *  Views integration for Workbench.
 *
 * @copyright (C) Copyright 2010 Palantir.net
 */

/**
 * Implements hook_views_data().
 */
function workbench_access_views_data() {
  $data = array();
  $data['workbench_access']['table']['group']  = t('Workbench Access');
  $data['workbench_access']['edit'] = array(
    'title' => t('Edit link'),
    'help' => t('A link to edit the node.'),
    'field' => array(
      'handler' => 'workbench_access_handler_field_edit_node',
      'click sortable' => FALSE,
    ),
  );
  $data['workbench_access']['section'] = array(
    'title' => t('Section'),
    'help' => t('The section to which this node belongs.'),
    'field' => array(
      'handler' => 'workbench_access_handler_field_section',
      'click sortable' => TRUE,
    ),
    'sort' => array(
      'handler' => 'workbench_access_handler_sort_section',
    ),
  );
  $data['workbench_access']['access_id'] = array(
    'title' => t('Access filter'),
    'help' => t('Filter nodes by access level.'),
    'filter' => array(
      'handler' => 'workbench_access_handler_filter_access',
    ),
  );

  $data['workbench_access_node']['table']['group']  = t('Workbench Access');
  $data['workbench_access_node']['nid'] = array(
    'title' => t('Nid'),
    'help' => t('Nid'),
  );
  $data['workbench_access_node']['table']['join'] = array(
    //...to the node table
    'node' => array(
      'left_field' => 'nid',
      'field' => 'nid',
    ),
  );
  $data['workbench_access']['table']['join'] = array(
    //...to the node table
    'node' => array(
      'left_field' => 'nid',
      'field' => 'nid',
    ),
  );
  return $data;
}

function workbench_access_views_handlers() {
  return array(
    'info' => array(
      'path' => drupal_get_path('module', 'workbench_access') . '/includes',
    ),
    'handlers' => array(
      'workbench_access_handler_field_edit_node' => array(
        'parent' => 'views_handler_field',
      ),
      'workbench_access_handler_field_section' => array(
        'parent' => 'views_handler_field',
      ),
      'workbench_access_handler_sort_section' => array(
        'parent' => 'views_handler_sort',
      ),
      'workbench_access_handler_filter_access' => array(
        'parent' => 'views_handler_filter',
      ),
    ),
  );
}

/**
 * Add dynamically the exposed fitlers that workbench_access provides to appropriate Views
 */
function workbench_access_views_pre_view(&$view, &$display_id, &$args) {

  //There should be a more intelligent check here for which Views/display_ids upon which to act
  //Right now it is only adding one View
  if ($view->name  == 'workbench_access_content') {
    $view->display_handler->options['filters']['access_id'] = _workbench_access_views_access_id_definition();
  }
}

/**
 * Return the array definition of the access_id exposed filter
 *
 * This is called from workbench_access_views_pre_view to dynamically
 * add this filter to given Views
 */
function _workbench_access_views_access_id_definition() {

  return array (
    'id' => 'access_id',
    'table' => 'workbench_access',
    'field' => 'access_id',
    'operator' => 'or',
    'exposed' => true,
    'expose' =>
    array (
      'label' => 'Sections',
      'identifier' => 'access_id',
      'remember' => 1,
      'single' => 0,
    ),
    'access_id' =>
    array (
    ),
    'size' => '5',
    'group' => '0',
  );
}
