<?php

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Database\Database;

$connection = Database::getConnection();

// Setup a 'section 1' tag.
$connection->insert('taxonomy_term_data')
  ->fields([
    'tid',
    'vid',
    'uuid',
    'langcode',
  ])
  ->values([
    'tid' => '1',
    'vid' => 'tags',
    'uuid' => '9e606865-9ffe-44ef-aba5-f3ea11a281bf',
    'langcode' => 'en',
  ])
  ->execute();

// Add a user named robbo with access to section 1.
$connection->insert('users')
  ->fields([
    'uid',
    'uuid',
    'langcode',
  ])
  ->values([
    'uid' => '3',
    'uuid' => 'd3b5a187-14f8-4d50-88f9-80565a01f885',
    'langcode' => 'en',
  ])
  ->execute();

$connection->insert('users_field_data')
  ->fields([
    'uid',
    'langcode',
    'preferred_langcode',
    'preferred_admin_langcode',
    'name',
    'pass',
    'mail',
    'timezone',
    'status',
    'created',
    'changed',
    'access',
    'login',
    'init',
    'default_langcode',
  ])
  ->values([
    'uid' => '3',
    'langcode' => 'en',
    'preferred_langcode' => 'en',
    'preferred_admin_langcode' => NULL,
    'name' => 'robbo',
    'pass' => Crypt::randomBytesBase64(),
    'mail' => 'foo@bar.com',
    'timezone' => '',
    'status' => '0',
    'created' => '1439727405',
    'changed' => '1439727405',
    'access' => '0',
    'login' => '0',
    'init' => NULL,
    'default_langcode' => '1',
  ])->execute();

$connection->schema()->createTable('user__field_workbench_access', [
  'fields' => [
    'bundle' => [
      'type' => 'varchar_ascii',
      'not null' => TRUE,
      'length' => '128',
      'default' => '',
    ],
    'deleted' => [
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'tiny',
      'default' => '0',
    ],
    'entity_id' => [
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ],
    'revision_id' => [
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ],
    'langcode' => [
      'type' => 'varchar_ascii',
      'not null' => TRUE,
      'length' => '32',
      'default' => '',
    ],
    'delta' => [
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ],
    'field_workbench_access_value' => [
      'type' => 'varchar',
      'length' => 255,
    ],
  ],
  'primary key' => [
    'entity_id',
    'deleted',
    'delta',
    'langcode',
  ],
  'indexes' => [
    'bundle' => [
      'bundle',
    ],
    'revision_id' => [
      'revision_id',
    ],
  ],
]);

$connection->insert('user__field_workbench_access')
  ->fields([
    'bundle',
    'deleted',
    'entity_id',
    'revision_id',
    'langcode',
    'delta',
    'field_workbench_access_value',
  ])
  ->values([
    'bundle' => 'user',
    'deleted' => '0',
    'entity_id' => '3',
    'revision_id' => '3',
    'langcode' => 'en',
    'delta' => '0',
    // Term ID.
    'field_workbench_access_value' => '1',
  ])
  ->execute();

// Add an editor role.
$role = [
  'uuid' => 'e9e18761-a893-4447-9617-09a74052cb1e',
  'id' => 'editors',
  'label' => 'Editors',
  'weight' => 1,
  'is_admin' => NULL,
  'permissions' => [
    'use workbench access',
    'create article content',
    'edit any article content',
    'delete any article content',
    'administer nodes',
    'access content',
  ],
];

$connection->insert('config')
  ->fields([
    'collection',
    'name',
    'data',
  ])
  ->values([
    'collection' => '',
    'name' => 'user.role.editors',
    'data' => serialize($role),
  ])
  ->execute();

// Grant that role access to section 1.
$connection->merge('key_value')
  ->condition('collection', 'state')
  ->condition('name', 'workbench_access_roles_editors')
  ->fields([
    'collection' => 'state',
    'name' => 'workbench_access_roles_editors',
    'value' => serialize([1]),
  ])
  ->execute();

// Configure workbench access settings.
$settings = [
  'scheme' => 'taxonomy',
  'parents' => ['tags'],
  'label' => 'Section',
  'plural_label' => 'Sections',
  'fields' => [
    'node' => [
      'article' => 'field_tags',
    ],
  ],
];
$connection->insert('config')
  ->fields([
    'collection',
    'name',
    'data',
  ])
  ->values([
    'collection' => '',
    'name' => 'workbench_access.settings',
    'data' => serialize($settings),
  ])
  ->execute();

// Remove some menus.
$connection->delete('config')
  ->condition('name', [
    'block.block.bartik_account_menu',
    'block.block.bartik_main_menu',
    'block.block.bartik_tools',
    'block.block.bartik_footer',
  ], 'IN')
  ->execute();

// Set the schema version.
$connection->merge('key_value')
  ->condition('collection', 'system.schema')
  ->condition('name', 'workbench_access')
  ->fields([
    'collection' => 'system.schema',
    'name' => 'workbench_access',
    'value' => 's:4:"8001";',
  ])
  ->execute();

// Update core.extension.
$extensions = $connection->select('config')
  ->fields('config', ['data'])
  ->condition('collection', '')
  ->condition('name', 'core.extension')
  ->execute()
  ->fetchField();
$extensions = unserialize($extensions);
$extensions['module']['workbench_access'] = 0;
// Disable block content, the dump has the wrong entity revision fields.
unset($extensions['module']['block_content']);
$connection->update('config')
  ->fields([
    'data' => serialize($extensions),
  ])
  ->condition('collection', '')
  ->condition('name', 'core.extension')
  ->execute();
