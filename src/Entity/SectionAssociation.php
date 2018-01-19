<?php

namespace Drupal\workbench_access\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Defines the workbench_access SectionAssociation class.
 *
 * @ContentEntityType(
 *   id = "section_association",
 *   label = @Translation("Section association"),
 *   bundle_label = @Translation("Section association"),
 *   handlers = {
 *     "access" = "Drupal\workbench_access\SectionAssociationAccessControlHandler",
 *     "storage" = "Drupal\workbench_access\SectionAssociationStorage",
 *     "storage_schema" = "Drupal\workbench_access\SectionAssociationStorageSchema",
 *     "views_data" = "\Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\workbench_access\SectionAssociationForm"
 *     }
 *   },
 *   admin_permission = "assign workbench access",
 *   base_table = "section_association",
 *   data_table = "section_association_data",
 *   translatable = FALSE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   }
 * )
 */
class SectionAssociation extends ContentEntityBase implements SectionAssociationInterface {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    // Standard field, used as unique if primary index.
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Section Association entity.'))
      ->setReadOnly(TRUE);

    // Standard field, unique outside of the scope of the current project.
    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Section Association entity.'))
      ->setReadOnly(TRUE);

    // Assigned users.
    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Users'))
      ->setDescription(t('The Name of the associated user.'))
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'author',
        'weight' => -3,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => '',
        ],
        'weight' => -3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Assigned roles.
    $fields['role_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Role'))
      ->setDescription(t('The roles associated with this section.'))
      ->setSetting('target_type', 'user_role')
      ->setSetting('handler', 'default')
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -2,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => '',
        ],
        'weight' => -3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

}
