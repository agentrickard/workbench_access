<?php

namespace Drupal\workbench_access;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Defines the section association schema handler.
 */
class SectionAssociationStorageSchema extends SqlContentEntityStorageSchema {

  /**
   * {@inheritdoc}
   */
  protected function getSharedTableFieldSchema(FieldStorageDefinitionInterface $storage_definition, $table_name, array $column_mapping) {
    $schema = parent::getSharedTableFieldSchema($storage_definition, $table_name, $column_mapping);

    $schema[$table_name]['unique keys'] = [
      'section_id',
      'section_schema_id',
    ];

    return $schema;
  }

}
