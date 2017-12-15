<?php

/**
 * @file
 * Contains post update hooks.
 */

use Drupal\workbench_access\Entity\AccessScheme;
use Drupal\workbench_access\RoleSectionStorageInterface;
use Drupal\workbench_access\WorkbenchAccessManagerInterface;

/**
 * Convert configuration into a scheme.
 */
function workbench_access_post_update_convert_to_scheme() {
  $config = \Drupal::configFactory()->getEditable('workbench_access.settings');
  if ($config->get('scheme') === 'taxonomy') {
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
      'vocabularies' => array_values($config->get('parents')),
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
  /** @var \Drupal\node\NodeTypeInterface $node_type */
  foreach (\Drupal::entityTypeManager()->getStorage('node_type')->loadMultiple() as $node_type) {
    $node_type->unsetThirdPartySetting('workbench_access', 'workbench_access_status');
    $node_type->save();
  }
  // Install our config entity.
  \Drupal::entityDefinitionUpdateManager()->applyUpdates();
}

/**
 * Convert role storage.
 */
function workbench_access_post_update_convert_role_storage_keys() {
  foreach (\Drupal::entityTypeManager()->getStorage('user_role')->loadMultiple() as $rid => $role) {
    $old_key = RoleSectionStorageInterface::WORKBENCH_ACCESS_ROLES_STATE_PREFIX . $rid;
    $new_key = RoleSectionStorageInterface::WORKBENCH_ACCESS_ROLES_STATE_PREFIX . 'default__' . $rid;
    $state = \Drupal::state();
    if ($existing = $state->get($old_key, FALSE)) {
      $state->set($new_key, $existing);
      $state->delete($old_key);
    }
  }
}

/**
 * Convert user storage.
 */
function workbench_access_post_update_convert_user_storage_keys(array &$sandbox) {
  $user_storage = \Drupal::entityTypeManager()->getStorage('user');
  if (!isset($sandbox['ids'])) {
    $sandbox['ids'] = $user_storage
      ->getQuery()
      ->exists(WorkbenchAccessManagerInterface::FIELD_NAME)
      ->execute();
    $sandbox['count'] = count($sandbox['ids']);
  }
  foreach (array_splice($sandbox['ids'], 0, 50) as $id) {
    $user = $user_storage->load($id);
    $existing = array_column($user->get(WorkbenchAccessManagerInterface::FIELD_NAME)->getValue(), 'value');
    $user->set(WorkbenchAccessManagerInterface::FIELD_NAME, array_map(function ($item) {
      return 'default:' . $item;
    }, $existing));
    $user->save();
  }

  $sandbox['#finished'] = empty($sandbox['ids']) ? 1 : ($sandbox['count'] - count($sandbox['ids'])) / $sandbox['count'];
  return t('Updated user assigments');
}
