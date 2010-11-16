<?php

/**
 * @file
 *  Taxonomy integration for Workbench.
 *
 * @copyright (C) Copyright 2010 Palantir.net
 */

/**
 * Implements hook_workbench_access_info().
 *
 * Defines the default handler for access controls.
 */
function taxonomy_workbench_access_info() {
  return array(
    'taxonomy' => array(
      'access_scheme' => 'taxonomy',
      'name' => 'Taxonomy',
      'access_type' => 'taxonomy',
      'access_type_id' => variable_get('workbench_access_taxonomy', 0),
      'description' => t('Uses a taxonomy vocabulary for assigning hierarchical access control'),
      'configuration' => 'taxonomy_workbench_access_configuration',
      'node_table' => 'workbench_access_node',
      'query_field' => 'access_id',
      'field_table' => 'workbench_access_node',
      'name_field' => 'name',
      'adjust_join' => array(
        'taxonomy_term_data' => array(
          'original_table' => 'taxonomy_index',
          'original_field' => 'tid',
          'new_table' => 'workbench_access_node',
          'new_field' => 'access_id',
        ),
      ),
      'sort' => array(
        array(
          'table' => 'taxonomy_term_hierarchy',
          'field' => 'parent',
        ),
        array(
          'table' => 'taxonomy_term_data',
          'field' => 'weight',
          'order' => 'ASC',
        ),
        array(
          'table' => 'taxonomy_term_data',
          'field' => 'name',
          'order' => 'ASC',
        ),
      ),
    ),
  );
}

/**
 * Defines configuration options for the default access scheme.
 *
 * @see workbench_access_workbench_access_info()
 */
function taxonomy_workbench_access_configuration(&$form, &$form_state) {
  $options = array();
  $vocabularies = taxonomy_get_vocabularies();
  foreach ($vocabularies as $vid => $vocabulary) {
    if ($vocabulary->module == 'workbench_access') {
      $options[$vid] = $vocabulary->name;
    }
  }
  $form['taxonomy_workbench_access_info'] = array(
    '#type' => 'fieldset',
    '#title' => t('Taxonomy scheme settings'),
    '#states' => array(
      'visible' => array(
        ':input[name=workbench_access]' => array('value' => 'taxonomy'),
      ),
    ),
  );
  $form['taxonomy_workbench_access_info']['workbench_access_taxonomy'] = array(
    '#type' => 'radios',
    '#title' => t('Editorial vocabulary'),
    '#description' => t('Select the vocabulary to be used for access control. <strong>Warning: changing this value in production may disrupt your workflow.</strong>'),
    '#options' => $options,
    '#default_value' => variable_get('workbench_access_taxonomy', 0),
    '#states' => array(
      'visible' => array(
        ':input[name=workbench_access]' => array('value' => 'taxonomy'),
      ),
    ),
  );
}

/**
 * Implements hook_workbench_access_field_form().
 */
function taxonomy_workbench_access_field_form(&$form, $form_state, $active) {
  _workbench_access_taxonomy_remove_option($form);
}

/**
 * Implements hook_workbench_access_field_ui_field_settings_form().
 */
function taxonomy_workbench_access_field_ui_field_settings_form(&$form, $form_state, $active) {
  _workbench_access_taxonomy_remove_option($form);
}

/**
 * Helper function to remove the option from Field UI forms.
 */
function _workbench_access_taxonomy_remove_option(&$form) {
  // The element we want is nested very deeply in the form.
  if (!isset($form['field']['settings']['allowed_values'])) {
    return;
  }
  // Cannot trust positional arrays.
  foreach ($form['field']['settings']['allowed_values'] as $key => $value) {
    if (isset($value['vocabulary']) && isset($form['field']['settings']['allowed_values'][$key]['vocabulary']['#options']['workbench_access'])) {
      unset($form['field']['settings']['allowed_values'][$key]['vocabulary']['#options']['workbench_access']);
    }
  }
}
