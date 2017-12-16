<?php

/**
 * @file
 * Drush commands for Workbench Access.
 */

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Language\LanguageInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\workbench_access\WorkbenchAccessManagerInterface;

/**
 * Implements hook_drush_command().
 */
function workbench_access_drush_command() {
  $items = [];

  $items['workbench-access-test'] = [
    'description' => 'Install test configuration of Workbench Access.',
    'aliases' => ['wa-test'],
  ];
  $items['workbench-access-flush'] = [
    'description' => 'Flush section assignments for users and roles.',
    'aliases' => ['wa-flush'],
  ];

  return $items;
}

/**
 * Implements hook_drush_help().
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
    $vocabulary = Vocabulary::create([
      'name' => 'Workbench Access',
      'description' => 'Test taxonomy for Workbench Access',
      'vid' => 'workbench_access',
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
      'weight' => 100,
    ]);
    $vocabulary->save();
    // Create some terms.
    $terms = [
      'Alumni',
      'Faculty',
      'Staff',
      'Students',
    ];
    $children = [
      'Directory',
      'Information',
    ];

    $filter_formats = filter_formats();
    $format = array_pop($filter_formats);
    foreach ($terms as $name) {
      $term = Term::create([
        'name' => $name,
        'description' => [],
        'vid' => $vocabulary->id(),
        'parent' => 0,
        'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
      ]);
      $term->save();
      foreach ($children as $child) {
        $child = Term::create([
          'name' => "$name $child",
          'description' => [],
          'vid' => $vocabulary->id(),
          'parent' => $term->id(),
          'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
        ]);
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
  $user_section_storage = \Drupal::getContainer()->get('workbench_access.user_section_storage');
  $role_section_storage = \Drupal::getContainer()->get('workbench_access.role_section_storage');
  $role_section_storage->flushRoles();
  $user_section_storage->flushUsers();
  foreach (\Drupal::entityTypeManager()->getStorage('access_scheme')->loadMultiple() as $scheme) {
    $scheme->delete();
  }
  drush_print('User and role assignments cleared. Access schemes deleted.');
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

/**
 * Sets up form display.
 */
function _workbench_access_form_display($entity_type, $bundle) {
  // Try loading the entity from configuration.
  $entity_form_display = EntityFormDisplay::load($entity_type . '.' . $bundle . '.default');

  if (!$entity_form_display) {
    $entity_form_display = EntityFormDisplay::create([
      'targetEntityType' => $entity_type,
      'bundle' => $bundle,
      'mode' => 'default',
      'status' => TRUE,
    ]);
  }

  $entity_form_display->setComponent(WorkbenchAccessManagerInterface::FIELD_NAME, [
    'type' => 'options_select',
    'weight' => 0,
  ])->save();

  return $entity_form_display;
}
