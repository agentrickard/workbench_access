<?php

/**
 * @file
 * Contains post update hooks.
 */

use Drupal\block\BlockInterface;
use Drupal\Core\Config\Entity\ConfigEntityUpdater;
use Drupal\Core\Utility\UpdateException;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\workbench_access\Entity\AccessScheme;
use Drupal\workbench_access\RoleSectionStorageInterface;
use Drupal\workbench_access\WorkbenchAccessManagerInterface;

/**
 * Convert configuration into a scheme.
 */
function workbench_access_post_update_convert_to_scheme() {
  $config = \Drupal::state()->get('workbench_access_original_configuration', FALSE);
  if (!$config) {
    throw new UpdateException('Did not find expected original configuration');
  }
  $settings = [];
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
  elseif ($config->get('scheme') === 'menu') {
    $settings = [
      'menus' => $config->get('parents'),
      'bundles' => array_keys($config->get('fields')['node']),
    ];
  }
  // Let other modules intervene for additional types.
  \Drupal::moduleHandler()->alter('workbench_access_scheme_update', $settings, $config);

  // No settings? Do nothing but mark the update.
  if (empty($settings)) {
    $message = t('Workbench Access has not been configured. Disabling the module is recommended.');
  }
  else {
    $scheme = AccessScheme::create([
      'id' => 'default',
      'label' => $config->get('label'),
      'plural_label' => $config->get('plural_label'),
      'scheme' => $config->get('scheme'),
      'scheme_settings' => $settings,
    ]);
    $scheme->save();
  }

  \Drupal::state()->set('workbench_access_upgraded_scheme_id', 'default');
  /** @var \Drupal\node\NodeTypeInterface $node_type */
  foreach (\Drupal::entityTypeManager()->getStorage('node_type')->loadMultiple() as $node_type) {
    $node_type->unsetThirdPartySetting('workbench_access', 'workbench_access_status');
    $node_type->save();
  }
  if (isset($message)) {
    return $message;
  }
}

/**
 * Convert role storage.
 */
function workbench_access_post_update_convert_role_storage_keys() {
  foreach (\Drupal::entityTypeManager()->getStorage('user_role')->loadMultiple() as $rid => $role) {
    $prefix = 'workbench_access_roles_';
    $old_key = $prefix . $rid;
    $new_key = $prefix . 'default__' . $rid;
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

/**
 * Transform existing role data to new storage.
 */
function workbench_access_post_update_section_role_association(&$sandbox) {
  $schemes = \Drupal::entityTypeManager()->getStorage('access_scheme')->loadMultiple();
  $storage = \Drupal::service('workbench_access.role_section_storage');
  $state = \Drupal::state();
  foreach ($schemes as $scheme) {
    foreach (\Drupal::entityTypeManager()->getStorage('user_role')->loadMultiple() as $rid => $role) {
      $prefix = 'workbench_access_roles_';
      $potential_ids = [
        $prefix . $rid,
        $prefix . 'default__' . $rid,
      ];
      foreach ($potential_ids as $key) {
        if ($existing = $state->get($key, FALSE)) {
          // Save the new roles.
          $storage->addRole($scheme, $rid, array_values($existing));
          // Delete the old storage.
          $state->delete($key);
        }
      }
    }
  }
}

/**
 * Transform existing user data to new storage.
 */
function workbench_access_post_update_section_user_association(&$sandbox) {
  $schemes = \Drupal::entityTypeManager()->getStorage('access_scheme')->loadMultiple();
  $storage = \Drupal::service('workbench_access.user_section_storage');
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
    foreach ($schemes as $scheme_id => $scheme) {
      $add_sections = [];
      foreach ($existing as $item) {
        $split = explode(':', $item);
        if ($split[0] == $scheme_id) {
          $add_sections[] = $split[1];
        }
      }
    }
    $storage->addUser($scheme, $user, $add_sections);
  }

  $sandbox['#finished'] = empty($sandbox['ids']) ? 1 : ($sandbox['count'] - count($sandbox['ids'])) / $sandbox['count'];
  return t('Updated user assigments');
}

/**
 * Delete the old workbench_access field.
 */
function workbench_access_post_update_workbench_access_field_delete(&$sandbox) {
  $field_storage = \Drupal::entityTypeManager()->getStorage('field_storage_config');
  if ($field_storage = FieldStorageConfig::loadByName('user', WorkbenchAccessManagerInterface::FIELD_NAME)) {
    if (!$field_storage->isDeleted()) {
      $field_storage->delete();
    }
  }
}

/**
 * Updates all instances of the WBA block to include context mappings.
 */
function workbench_access_post_update_apply_context_mapping_to_blocks(&$sandbox) {
  \Drupal::classResolver(ConfigEntityUpdater::class)->update($sandbox, 'block', function(BlockInterface $block) {
    if ($block->getPluginId() === 'workbench_access_block') {
      $settings = $block->get('settings');
      if (!isset($settings['context_mapping']['node'])) {
        $settings['context_mapping']['node'] = '@node.node_route_context:node';
      }
      $block->set('settings', $settings);
      return TRUE;
    }
    return FALSE;
  });
}
