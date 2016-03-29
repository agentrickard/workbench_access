<?php

/**
 * @file
 * Contains \Drupal\workbench_access\WorkbenchAccessViewsData.
 */

namespace Drupal\workbench_access;

use Drupal\views\EntityViewsData;

/**
 * Provides the views data for Workbench Access.
 */
class WorkbenchAccessViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    // If config... add proper table definitions.
    // Get the field for Taxonomy.
    // What field to use for Menu Links?

    // Handles nodes and users.
    // Users might be handled for us, though the filter probably needs to have
    // the list of choices restricted.

    return $data;
  }

}
