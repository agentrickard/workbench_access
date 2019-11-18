<?php

/**
 * @file
 * Contains database additions for testing block upgrade path.
 */

use Drupal\Core\Database\Database;
use Drupal\Core\Serialization\Yaml;

$connection = Database::getConnection();

// Workbench block without context mapping.
$block_configs[] = Yaml::decode(file_get_contents(__DIR__ . '/block.block.workbench_access_block.yml'));

foreach ($block_configs as $block_config) {
  $connection->insert('config')
    ->fields([
      'collection',
      'name',
      'data',
    ])
    ->values([
      'collection' => '',
      'name' => 'block.block.' . $block_config['id'],
      'data' => serialize($block_config),
    ])
    ->execute();
}

// Enable block module.
$config = unserialize($connection->query("SELECT data FROM {config} where name = :name", [':name' => 'core.extension'])->fetchField());
$config['module']['block'] = 0;
$connection->update('config')
  ->fields(['data' => serialize($config)])
  ->condition('name', 'core.extension')
  ->execute();

$connection->insert('key_value')
  ->fields([
    'value' => serialize(['block.block.workbench_access_block']),
    'collection' => 'config.entity.key_store.block',
    'name' => 'theme:classy',
  ])
  ->execute();
