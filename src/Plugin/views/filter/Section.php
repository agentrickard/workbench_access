<?php

/**
 * @file
 * Contains \Drupal\workbench_access\Plugin\views\filter\Section.
 */

namespace Drupal\workbench_access\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\filter\ManyToOne;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Views;
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
    $this->manager = \Drupal::getContainer()->get('plugin.manager.workbench_access.scheme');
    $this->scheme = $this->manager->getActiveScheme();
  }

  public function getValueOptions() {
    if (isset($this->valueOptions)) {
      return $this->valueOptions;
    }
    $this->valueOptions = [];
    if (!empty($this->scheme)) {
      foreach($this->manager->getUserSections() as $id) {
        $section = $this->manager->getElement($id);
        $this->valueOptions[$id] = str_repeat('-', $section['depth']) . ' ' . $section['label'];
      }
    }

    return $this->valueOptions;
  }

  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['operator']['default'] = 'in';
    $options['value']['default'] = array();

    return $options;
  }

  public function defaultExposeOptions() {
    parent::defaultExposeOptions();
    $this->options['expose']['reduce'] = TRUE;
  }

  function operators() {
    $operators = array(
      'in' => array(
        'title' => $this->t('Is one of'),
        'short' => $this->t('in'),
        'short_single' => $this->t('='),
        'method' => 'opSimple',
        'values' => 1,
      ),
      'not in' => array(
        'title' => $this->t('Is not one of'),
        'short' => $this->t('not in'),
        'short_single' => $this->t('<>'),
        'method' => 'opSimple',
        'values' => 1,
      ),
    );
    return $operators;
  }

  public function query() {
    $info = $this->operators();
    $helper = new ManyToOneHelper($this);
    foreach ($this->scheme->getViewsJoin() as $configuration) {
      $join = Views::pluginManager('join')->createInstance('standard', $configuration);
      $this->tableAlias = $helper->addTable($join, $configuration['table_alias']);
      $this->realField = $configuration['real_field'];
    }
    if (empty($this->value)) {
      return;
    }
    if ($values = $this->getChildren()) {
      $this->scheme->addWhere($this, $values);
    }
  }

  protected function getChildren() {
    $tree = $this->scheme->getTree();
    $children = [];
    foreach ($this->value as $id) {
      foreach ($tree as $key => $data) {
        if ($id == $key) {
          $children += array_keys($data);
        }
        else {
          foreach ($data as $iid => $item) {
            if ($iid == $id || in_array($id, $item['parents'])) {
              $children[] = $iid;
            }
          }
        }
      }
    }
    return $children;
  }
}
