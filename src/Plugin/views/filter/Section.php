<?php

/**
 * @file
 * Contains \Drupal\workbench_access\Plugin\views\filter\Section.
 */

namespace Drupal\workbench_access\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\filter\ManyToOne;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ViewExecutable;
use Drupal\views\ManyToOneHelper;

/**
 * Filter by assigned section.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("workbench_access_section")
 */
class Section extends ManyToOne {

  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);
    $this->additional_fields['nid'] = 'nid';
  }

  public function getValueOptions() {
    if (isset($this->valueOptions)) {
      return $this->valueOptions;
    }
    $this->valueOptions = [];
    $manager = \Drupal::getContainer()->get('plugin.manager.workbench_access.scheme');
    if ($scheme = $manager->getActiveScheme()) {
      foreach($manager->getUserSections() as $id) {
        $section = $manager->getElement($id);
        $this->valueOptions[$id] = str_repeat('-', $section['depth']) . ' ' . $section['label'];
      }
    }
    return $this->valueOptions;
  }

  public function query() {
    $info = $this->operators();
    $helper = new ManyToOneHelper($this);
    $fields = $this->getFields();
    foreach ($fields['node'] as $field) {
      if (!empty($field)) {
kint($field);
        $helper->addTable();
      }
    }
    if (!empty($info[$this->operator]['method'])) {
      $this->{$info[$this->operator]['method']}();
    }
  }

  public function getFields() {
    $fields = [];
    $manager = \Drupal::getContainer()->get('plugin.manager.workbench_access.scheme');
    if ($scheme = $manager->getActiveScheme()) {
      $config_factory = \Drupal::getContainer()->get('config.factory');
      $config = $config_factory->get('workbench_access.settings');
      $fields = $config->get('fields');
    }
    return $fields;
  }

}
