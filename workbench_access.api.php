<?php

/**
 * @file
 * APU documentation for Workbench Access.
 */

/**
 * Converts scheme settings to use the AccessScheme entity type.
 *
 * @param array $settings
 *   An array of settings for the plugin. Likely empty. Be certain to only act
 *   on your plugin scheme.
 *
 * @param Drupal\Core\Config\Config $config
 *   Current data object for Workbench Access configuration.
 *
 * @return
 *   No return value. Modify $settings by reference to match the array defined
 *   by your plugin's implementation of
 *   AccessControlHierarchyInterface::defaultConfiguration().
 */
function hook_workbench_access_scheme_update_alter(array &$settings, Drupal\Core\Config\Config $config) {
  if ($config->get('scheme') === 'my_plugin_scheme') {
    $fields = [];
    foreach ($config->get('fields') as $entity_type => $field_info) {
      foreach (array_filter($field_info) as $bundle => $field_name) {
        $fields[] = [
          'entity_type' => $entity_type,
          'bundle' => $bundle,
          'field' => $field_name,
        ];
      }
    }
    $settings = [
      'my_scheme_type' => array_values($config->get('parents')),
      'fields' => $fields,
    ];
  }
}
