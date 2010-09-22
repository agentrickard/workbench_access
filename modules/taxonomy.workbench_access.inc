<?php

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
      'node_table' => 'taxonomy_index',
      'query_field' => 'tid',
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
    $options[$vid] = $vocabulary->name;
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
