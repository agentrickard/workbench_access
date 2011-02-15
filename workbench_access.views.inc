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
 * Implementation of hook_view_pre_view().
 *
 * Add dynamically the exposed fitlers that workbench_access provides to appropriate Views.
 *
 * There are a lot of 'if () {} else {}' statements in here to find out which options to target.
 * These look at a given option and check if it uses the default settings or an override.
 * The given option is then modified to add filters/fields/settings from workbench_access.
 */
function workbench_access_views_pre_view(&$view, &$display_id, &$args) {

  // Target any view with a workbench tag that uses 'node' as the base_table.
  if (is_numeric(strripos($view->tag, 'Workbench')) && $view->base_table == 'node') {

    // This shorthand variable will increase readability.
    $current_display = $view->current_display;

    // If the current display uses the default filters, target the default filters.
    if ($current_display== 'default' || $view->display[$current_display]->handler->options['defaults']['filters']) {
      $filters = &$view->display['default']->handler->options['filters'];
    }
    // Otherwise, target this display's filters.
    else {
      $filters = &$view->display[$current_display]->handler->options['filters'];
    }

    // Add the access_id filter to the filters array.
    $filters['access_id'] = _workbench_access_views_access_id_filter_definition();

    // If the current display uses the default style_plugin, target the default style_plugin and style options.
    if ($current_display== 'default' || $view->display[$current_display]->handler->options['defaults']['style_plugin']) {
      $style_plugin  = &$view->display['default']->handler->options['style_plugin'];
      $style_options = &$view->display['default']->handler->options['style_options'];
    }
    // Otherwise, target this display's style_plugin and style options.
    else {
      $style_plugin = &$view->display[$current_display]->handler->options['style_plugin'];
      $style_options = &$view->display[$current_display]->handler->options['style_options'];
    }

    // Only Add a section field and style options if this is a table.
    if ($style_plugin == 'table') {

      // If the current display uses the default fields, target the default fields.
    if ($current_display== 'default' || $view->display[$current_display]->handler->options['defaults']['fields']) {
        $fields = &$view->display['default']->handler->options['fields'];
      }
      // Otherwise, target this display's fields.
      else {
        $fields = &$view->display[$current_display]->handler->options['fields'];
      }

      // This temporary variable will take all the values from
      // the existing fields array and add a section field after the title.
      $new_view_fields = array();
      foreach ($fields as $key => $value) {
        $new_view_fields[$key] = $value;
        if ($key == 'title') {
          $new_view_fields['section'] = _workbench_access_views_section_field_definition();
        }
      }

      // Set the new fields array on the actual view.
      $fields = $new_view_fields;

      $style_options['columns']['section'] = 'section';
      $style_options['info']['section'] = array(
        'sortable' => 1,
        'align' => '',
        'separator' => '',
      );
    }
  }
}

/**
 * Return the array definition of the access_id exposed filter.
 *
 * This is called from workbench_access_views_pre_view() to dynamically.
 * add this filter to given Views.
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
 * Return the array definition of the section field.
 *
 * This is called from workbench_access_views_pre_view() to dynamically
 * add this field to given Views.
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
