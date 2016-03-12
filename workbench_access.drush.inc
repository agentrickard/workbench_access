<?php

/**
 * @file
 * Drush commands for Workbench Access.
 */

use Drupal\Core\Language\LanguageInterface;

/**
 * Implements hook_drush_command().
 */
function workbench_access_drush_command() {
  $items = array();

  $items['workbench-access-test'] = array(
    'description' => 'Install test configuration of Workbench Access.',
    'aliases' => array('wa-test'),
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
  workbench_access_confirm_fields('user', 'user');
}

