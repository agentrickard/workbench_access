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

  public function query() {
    $info = $this->operators();
    $helper = new ManyToOneHelper($this);
    $fields = $this->scheme->fieldsByEntityType('node');
    foreach ($fields as $field) {
      if (!empty($field)) {
        $configuration = [
         'table' => 'node__' . $field,
         'field' => 'entity_id',
         'left_table' => 'node',
         'left_field' => 'nid',
         'operator' => '=',
        ];
        $join = Views::pluginManager('join')->createInstance('standard', $configuration);
        $this->tableAlias = $helper->addTable($join, $field);
        $this->realField = $field . '_target_id';
      }
    }
    if (!empty($info[$this->operator]['method'])) {
      $this->{$info[$this->operator]['method']}();
    }
  }

  protected function opHelper() {
    if (empty($this->value)) {
      return;
    }
    if ($values = $this->getChildren()) {
      $this->query->addWhere($this->options['group'], "$this->tableAlias.$this->realField", array_values($values), 'IN');
    }
  }

  protected function opSimple() {
    if (empty($this->value)) {
      return;
    }
    $this->ensureMyTable();

    // We use array_values() because the checkboxes keep keys and that can cause
    // array addition problems.
    $this->query->addWhere($this->options['group'], "$this->tableAlias.$this->realField", array_values($this->value), $this->operator);
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
