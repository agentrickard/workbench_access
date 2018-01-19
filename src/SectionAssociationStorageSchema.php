<?php

namespace Drupal\workbench_access;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;

/**
 * Defines the section association schema handler.
 */
class SectionAssociationStorageSchema extends SqlContentEntityStorageSchema {

  /**
   * {@inheritdoc}
   */
  protected function getEntitySchema(ContentEntityTypeInterface $entity_type, $reset = FALSE) {
    $schema = parent::getEntitySchema($entity_type, $reset);

    // Creates unique keys to guarantee the integrity of the entity.
    // We cannot have more than one entry per section.
    $unique_keys = [
      'section_id',
      'section_scheme_id',
    ];
    $schema['section_association_field_data']['unique keys'] += [
      'section_association_data__lookup' => $unique_keys,
    ];
    $schema['section_association_field_revision_data']['unique keys'] += [
      'section_association_data__lookup' => $unique_keys,
    ];

    return $schema;
  }

}
