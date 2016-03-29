<?php

/**
 * @file
 * Contains \Drupal\workbench_access\Plugin\views\field\Section.
 */

namespace Drupal\workbench_access\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;

/**
 * Field handler to present the section assigned to the node.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("workbench_access_section")
 */
class Section extends FieldPluginBase {

}
