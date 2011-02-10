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

/**
 * Implementation of hook_views_handlers
 */
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

  //Target any view with a workbench tag that uses the base_table, node
  if (is_numeric(strripos($view->tag, 'Workbench')) && $view->base_table == 'node') {
    $view->display_handler->options['filters']['access_id'] = _workbench_access_views_access_id_filter_definition();

    //Add a section field and style options if this is a table
    if ($view->display_handler->options['style_plugin'] == 'table') {

      //This array will be spliced and remerged after inserting the new section field
      $old_view_fields = $view->display_handler->options['fields'];

      //Get the number of fields
      $count = count($old_view_fields);

      //We're assuming here, using base 0, that the title will always be the first item in the fields array
      //That might not be a safe assumption
      $position_of_title = 0;
      $length_of_remainder = $count - ($position_of_title + 1);

      //Slice from the beginning of the array to after title, this may only one element, the title
      $new_view_fields = array_slice($old_view_fields, 0, ($position_of_title + 1), TRUE);

      //Add the section field
      $new_view_fields['section'] = _workbench_access_views_section_field_definition();

      //Add the rest of the items from the old array of fields
      $array_after_title =  array_slice($old_view_fields, -$length_of_remainder, $length_of_remainder, TRUE);
      $new_view_fields = array_merge($new_view_fields, $array_after_title);

      //Set the new fields array on the actual view
      $view->display_handler->options['fields'] = $new_view_fields;

      $view->display_handler->options['style_options']['columns']['section'] = 'section';
      $view->display_handler->options['style_options']['info']['section'] = array(
        'sortable' => 1,
        'align' => '',
        'separator' => '',
      );
    }
  }
}

/**
 * Return the array definition of the access_id exposed filter
 *
 * This is called from workbench_access_views_pre_view to dynamically
 * add this filter to given Views
 */
function _workbench_access_views_access_id_filter_definition() {

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

/**
 * Return the array definition of the section field
 *
 * This is called from workbench_access_views_pre_view to dynamically
 * add this field to given Views
 */
function _workbench_access_views_section_field_definition() {
 return array (
    'id' => 'section',
    'table' => 'workbench_access',
    'field' => 'section',
    'alter' =>
      array (
        'alter_text' => 0,
        'make_link' => 0,
        'absolute' => 0,
        'trim' => 0,
        'word_boundary' => 1,
        'ellipsis' => 1,
        'strip_tags' => 0,
        'html' => 0,
      ),
    'hide_empty' => 0,
    'empty_zero' => 0,
  );
}
