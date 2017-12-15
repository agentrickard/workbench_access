<?php

/**
 * @file
 * Contains post update hooks.
 */

use Drupal\workbench_access\Entity\AccessScheme;

/**
 * Convert configuration into a scheme.
 */
function workbench_access_post_update_convert_to_scheme() {
  $config = \Drupal::configFactory()->getEditable('workbench_access.settings');
  if ($config->get('scheme') === 'taxonomy') {
    $fields = [];
    foreach ($config->get('fields') as $entity_type => $field_info) {
      foreach ($field_info as $bundle => $field_name) {
        $fields[] = [
          'entity_type' => $entity_type,
          'bundle' => $bundle,
          'field' => $field_name,
        ];
      }
    }
    $settings = [
      'vocabularies' => $config->get('parents'),
      'fields' => $fields,
    ];
  }
  else {
    $settings = [
      'menus' => $config->get('parents'),
      'bundles' => array_keys($config->get('fields')['node']),
    ];
  }
  $scheme = AccessScheme::create([
    'id' => 'default',
    'label' => $config->get('label'),
    'plural_label' => $config->get('plural_label'),
    'scheme' => $config->get('scheme'),
    'scheme_settings' => $settings,
  ]);
  $scheme->save();
  \Drupal::state()->set('workbench_access_upgraded_scheme_id', 'default');
  foreach (['scheme', 'label', 'plural_label', 'fields', 'parents'] as $delete) {
    $config->delete();
  }
  $config->save(TRUE);
}
