<?php

namespace Drupal\workbench_access\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a hierarchical access control annotation object.
 *
 * Plugin Namespace: Plugin\AccessControlHierarchy
 *
 * For a working example, see
 * \Drupal\workbench_access\Plugin\AccessControlHierarchy\Taxonomy.
 *
 * Modules should use Drupal\workbench_access\AccessControlHierarchyBase as
 * a basis for new implementations.
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
   * The module required by the plugin.
   *
   * @var string
   */
  public $module;

  /**
   * The entity that defines an access control group. (Optional)
   *
   * If this value is not set, your plugin class will need to provide it's own
   * version of the options() method.
   *
   * @var string
   */
  public $base_entity;

  /**
   * The entity that defines an access control item. (Optional)
   *
   * @var string
   */
  public $entity;

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
