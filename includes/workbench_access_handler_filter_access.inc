<?php

/**
 * @file
 *  Views integration for Workbench.
 *
 * @copyright (C) Copyright 2010 Palantir.net
 */

class workbench_access_handler_filter_access extends views_handler_filter {
  function option_definition() {
    $options = parent::option_definition();
    $options['access_id'] = array('default' => NULL);
    $options['size'] = array('default' => 5);
    return $options;
  }

  function operator_options() {
    return array(
      'or' => t('Is one of'),
    );
  }

  function value_form(&$form, &$form_state) {
   # $options = array(-5 => t('All sections'));
    $active = workbench_access_get_active_tree();
    $tree = $active['tree'];
    workbench_access_build_tree($tree, array_keys($active['active']));
    $options = workbench_access_options($tree);
    $form['access_id'] = array(
      '#type' => 'select',
      '#title' => t('Sections'),
      '#multiple' => TRUE,
      '#options' => $options,
      '#default_value' => $this->options['access_id'],
      '#size' => $this->options['size'],
    );
    $form['size'] = array(
      '#type' => 'select',
      '#title' => t('Size'),
      '#options' => drupal_map_assoc(array(1, 5, 10, 20, 50)),
      '#default_value' => $this->options['size'],
    );
  }

  function exposed_form(&$form, &$form_state) {
    // Prevent parent form errors by using a value.
    $form['value'] = array('#type' => 'value', '#value' => '');
    parent::exposed_form(&$form, &$form_state);
    global $user;
    $account = $user;
    if (!isset($account->workbench_access)) {
      workbench_access_load_user_data($account);
    }
    $active = workbench_access_get_active_tree();
    $tree = $active['tree'];
    workbench_access_build_tree($tree, array_keys($account->workbench_access));
    $options = workbench_access_options($tree);
    $form['access_id'] = array(
      '#type' => 'select',
      '#multiple' => TRUE,
      '#options' => $options,
      '#default_value' => $this->options['access_id'],
      '#size' => $this->options['size'],
    );
    unset($form['size']);
  }

  function exposed_submit($form, &$form_state) {
    if (empty($form_state['values']['access_id'])) {
      $form_state['values']['access_id'] = -5;
    }
  }

  function query() {
    global $user;
    $account = $user;
    if (!isset($account->workbench_access)) {
      workbench_access_load_user_data($account);
    }
    if (empty($account->workbench_access)) {
      $table = $this->query->ensure_table('node');
      $field = $this->query->add_field('node', 'nid');
      $this->query->add_where($this->options['group'], "$table.$field", -1, '=');
      return;
    }
    $active = workbench_access_get_active_tree();
    $tree = $active['tree'];
    // No selection? Use the user's tree.
    if (empty($this->value) || $this->value == -5) {
      workbench_access_build_tree($tree, array_keys($account->workbench_access));
    }
    // Build the selection tree.
    else {
      workbench_access_build_tree($tree, array_keys($this->value));
    }
    $ids = array_keys($tree);
    $table = $this->query->ensure_table($active['access_scheme']['node_table']);
    // Using this field alias breaks the COUNT query. Views bug?
    $field = $this->query->add_field($table, $active['access_scheme']['query_field']);
    $this->query->add_where($this->options['group'], "$table." . $active['access_scheme']['query_field'], $ids, 'IN');
  }
}
