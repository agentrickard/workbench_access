<?php

namespace Drupal\workbench_access;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;

/**
 * Defines section association storage.
 *
 * @TODO: write the interface.
 */
class SectionAssociationStorage extends SqlContentEntityStorage {

  /**
   * {@inheritdoc}
   */
  public function loadSection($access_scheme_id, $section_id) {
    $section = $this->loadByProperties([
      'access_scheme' => $access_scheme_id,
      'section_id' => $section_id,
    ]);
    return current($section);
  }

}
