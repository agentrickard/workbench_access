<?php

/**
 * @file
 * Contains \Drupal\workbench_access\Annotation\AccessControlHierarchy.
 */

namespace Drupal\workbench_access\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a hierarchical access control annotation object.
 *
 * Plugin Namespace: Plugin\AccessControlHierarchy
 *
 * For a working example, see
 * \Drupal\workbench_access\Plugin\AccessControlHierarchy\Taxonomy
 *
 * @see plugin_api
 *
 * @Annotation
 */
class AccessControlHierarchy extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the hierarchy system.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

  /**
   * A brief description of the hierarchy source.
   *
   * This will be shown when adding or configuring Workbench Access.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation (optional)
   */
  public $description = '';

}
