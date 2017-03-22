<?php

/**
 * @file
 * Drush commands for Workbench Access.
 */

use Drupal\Core\Language\LanguageInterface;
use Drupal\workbench_access\WorkbenchAccessManagerInterface;

/**
 * Implements hook_drush_command().
 */
function workbench_access_drush_command() {
  $items = array();

  $items['workbench-access-test'] = array(
    'description' => 'Install test configuration of Workbench Access.',
    'aliases' => array('wa-test'),
  );
  $items['workbench-access-flush'] = array(
    'description' => 'Flush section assignments for users and roles.',
    'aliases' => array('wa-flush'),
  );

  return $items;
}

/**
 * Implement hook_drush_help().
 */
function workbench_access_drush_help($section) {
  $items = workbench_access_drush_command();
  $name = str_replace('workbench access:', '', $section);
  if (isset($items[$name])) {
    return dt($items[$name]['description']);
  }
}

/**
 * Installs the test configuration.
 */
function drush_workbench_access_test() {
  try {
    // Create a vocabulary.
    $vocabulary = entity_create('taxonomy_vocabulary', array(
      'name' => 'Workbench Access',
      'description' => 'Test taxonomy for Workbench Access',
      'vid' => 'workbench_access',
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
      'weight' => 100,
    ));
    $vocabulary->save();
    // Create some terms.
    $terms = array(
     'Alumni',
     'Faculty',
     'Staff',
     'Students',
    );
    $children = array(
     'Directory',
     'Information',
    );

    $filter_formats = filter_formats();
    $format = array_pop($filter_formats);
    foreach ($terms as $name) {
      $term = entity_create('taxonomy_term', array(
        'name' => $name,
        'description' => array(),
        'vid' => $vocabulary->id(),
        'parent' => 0,
        'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
      ));
      $term->save();
      foreach ($children as $child) {
        $child = entity_create('taxonomy_term', array(
          'name' => "$name $child",
          'description' => array(),
          'vid' => $vocabulary->id(),
          'parent' => $term->id(),
          'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
        ));
        $child->save();
      }
    }
  }
  catch (Exception $e) {
    drush_print('The test vocabulary has already been created.');
  }
  try {
    workbench_access_confirm_fields('user', 'user');
    $node_types = \Drupal::entityTypeManager()->getStorage('node_type')->loadMultiple();
    _workbench_access_create_node_storage();
    foreach ($node_types as $id => $type) {
      if ($id != 'page') {
        workbench_access_confirm_fields('node', $id);
        $type->setThirdPartySetting('workbench_access', 'workbench_access_status', 1);
        $type->save();
        $fields['node'][$id] = WorkbenchAccessManagerInterface::FIELD_NAME;
        _workbench_access_form_display('node', $id);
      }
    }
    if (isset($fields)) {
      $config = \Drupal::configFactory()->getEditable('workbench_access.settings');
      $config->set('scheme', 'taxonomy');
      $config->set('parents', ['workbench_access' => 'workbench_access']);
      $config->set('fields', $fields);
      $config->save();
    }
  }
  catch (Exception $e) {
    drush_print('Field creation failed.');
  }
}

/**
 * Flushes assigned user permissions.
 */
function drush_workbench_access_flush() {
  $manager = \Drupal::getContainer()->get('plugin.manager.workbench_access.scheme');
  $manager->flushRoles();
  $manager->flushUsers();
  $manager->flushFields();
  drush_print('User and role assignments cleared. Field assigments reset.');
}

/**
 * Creates a node storage field for testing.
 */
function _workbench_access_create_node_storage() {
  try {
    $field_storage_values = [
      'field_name' => WorkbenchAccessManagerInterface::FIELD_NAME,
      'entity_type' => 'node',
      'type' => 'entity_reference',
      'translatable' => TRUE,
      'settings' => ['target_type' => 'taxonomy_term'],
      'cardinality' => 1,
      'dependencies' => ['module' => ['node', 'taxonomy', 'workbench_access']],
    ];
    $manager = \Drupal::entityTypeManager();
    $manager->getStorage('field_storage_config')->create($field_storage_values)->save();
  }
  catch (Exception $e) {
    drush_print('Field storage creation failed.');
  }

}

function _workbench_access_form_display($entity_type, $bundle) {
  // Tell the form system how to behave. Default to radio buttons.
  // @TODO: This function is deprecated, but using the OO syntax is causing
  // test fails.
  entity_get_form_display($entity_type, $bundle, 'default')
    ->setComponent(WorkbenchAccessManagerInterface::FIELD_NAME, array(
      'type' => 'options_select',
      'weight' => 0,
    ))
    ->save();

}
