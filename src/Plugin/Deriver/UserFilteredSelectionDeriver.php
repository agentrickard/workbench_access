<?php

namespace Drupal\workbench_access\Plugin\Deriver;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a selection handler deriver for filtered users.
 */
class UserFilteredSelectionDeriver extends TaxonomyHierarchySelectionDeriver {

  /**
   * {@inheritdoc}
   */
  protected $label = 'Filtered user selection: @name';

}
