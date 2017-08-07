<?php

namespace Drupal\workbench_access\Plugin\EntityReferenceSelection;

use Drupal\user\Plugin\EntityReferenceSelection\UserSelection;

/**
 * Provides specific access control for the taxonomy_term entity type.
 *
 * @EntityReferenceSelection(
 *   id = "workbench_access:user",
 *   label = @Translation("Filtered user selection"),
 *   entity_types = {"user"},
 *   group = "workbench_access",
 *   weight = 1,
 *   base_plugin_label = @Translation("Workbench Access: Filtered user selection")
 * )
 */
class UserFilteredSelection extends UserSelection {

  /**
   * {@inheritdoc}
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $query = parent::buildEntityQuery($match, $match_operator);
    $handler_settings = $this->configuration['handler_settings'];

    // Filter out the already referenced users.
    if (isset($handler_settings['filter']['section_id'])) {
      $id = $handler_settings['filter']['section_id'];
      $user_section_storage = \Drupal::getContainer()->get('workbench_access.user_section_storage');
      $editors = $user_section_storage->getEditors($id);
      if (count($editors)) {
        $query->condition('uid', array_keys($editors), 'NOT IN');
      }
    }

    return $query;
  }

}
