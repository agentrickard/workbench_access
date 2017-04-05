<?php

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

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);
    $this->manager = \Drupal::getContainer()->get('plugin.manager.workbench_access.scheme');
    $this->scheme = $this->manager->getActiveScheme();
  }

  /**
   * {@inheritdoc}
   */
  public function getValueOptions() {
    if (isset($this->valueOptions)) {
      return $this->valueOptions;
    }
    $this->valueOptions = [];
    if (!empty($this->scheme)) {
      if ($this->manager->userInAll()) {
        $list = $this->manager->getAllSections();
      }
      else {
        $list = $this->manager->getUserSections();
      }
      foreach($list as $id) {
        if ($section = $this->manager->getElement($id)) {
          $this->valueOptions[$id] = str_repeat('-', $section['depth']) . ' ' . $section['label'];
        }
      }
    }
    return $this->valueOptions;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['operator']['default'] = 'in';
    $options['value']['default'] = array('All');
    $options['expose']['contains']['reduce'] = array('default' => TRUE);
    $options['section_filter']['contains']['show_hierarchy'] = array('default' => TRUE);

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultExposeOptions() {
    parent::defaultExposeOptions();
    $this->options['expose']['reduce'] = TRUE;
  }

  /**
   * {@inheritdoc}
   */
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

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $form['section_filter']['show_hierarchy'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show children'),
      '#default_value' => !empty($this->options['section_filter']['show_hierarchy']),
      '#description' => $this->t('If checked, the filter will return the selected item and all its children.'),
    ];
  }

  /**
   * {@inheritdoc}
   *
   * Check to see if input from the exposed filters should change
   * the behavior of this filter.
   *
   * We change this default behavior, since our "Any" result should be filtered
   * by the user's assignments.
   */
  public function acceptExposedInput($input) {
    if (empty($this->options['exposed'])) {
      return TRUE;
    }

    if (!empty($this->options['expose']['use_operator']) && !empty($this->options['expose']['operator_id']) && isset($input[$this->options['expose']['operator_id']])) {
      $this->operator = $input[$this->options['expose']['operator_id']];
    }

    if (!empty($this->options['expose']['identifier'])) {
      $value = $input[$this->options['expose']['identifier']];

      // Various ways to check for the absence of non-required input.
      if (empty($this->options['expose']['required'])) {
        if (($this->operator == 'empty' || $this->operator == 'not empty') && $value === '') {
          $value = ' ';
        }
      }

      // We removed two clauses here that cause the filter to be ignored.

      if (isset($value)) {
        $this->value = $value;
        if (empty($this->alwaysMultiple) && empty($this->options['expose']['multiple']) && !is_array($value)) {
          $this->value = array($value);
        }
      }
      else {
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $helper = new ManyToOneHelper($this);
    // The 'All' selection must be filtered by user sections.
    if (empty($this->value) || strtolower(current($this->value)) == 'all') {
      if ($this->manager->userInAll()) {
        return;
      }
      else {
        // This method will get all user sections and children.
        $values = $this->manager->getUserSections();
      }
    }
    if (!empty($this->table)) {
      $alias = $this->query->ensureTable($this->table);
      foreach ($this->scheme->getViewsJoin($this->table, $this->realField, $alias) as $configuration) {
        // Allow subquery JOINs, which Menu users.
        $type = 'standard';
        if (isset($configuration['left_query'])) {
          $type = 'subquery';
        }
        $join = Views::pluginManager('join')->createInstance($type, $configuration);
        $this->tableAlias = $helper->addTable($join, $configuration['table_alias']);
        $this->realField = $configuration['real_field'];
      }
      // If 'All' was not selected, fetch the query values.
      if (!isset($values)) {
        if (!empty($this->options['section_filter']['show_hierarchy'])) {
          $values = $this->getChildren();
        }
        else {
          $values = $this->value;
        }
      }
      // If values, add our standard where clause.
      if (!empty($values)) {
        $this->scheme->addWhere($this, $values);
      }
      // Else add a failing where clause.
      else {
        $this->query->addWhere($filter->options['group'], '1 = 0');
      }
    }
  }

  /**
   * Gets the child sections of a base section.
   *
   * @return array
   *   An array of section ids that this user may see.
   */
  protected function getChildren() {
    $tree = $this->manager->getActiveTree();
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
