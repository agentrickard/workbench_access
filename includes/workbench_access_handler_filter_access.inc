<?php

/**
 * @file
 * Views integration for Workbench.
 *
 * Provides a filter to display nodes by assigned section.
 */

class workbench_access_handler_filter_access extends views_handler_filter_many_to_one {
  function init(&$view, &$options) {
    parent::init($view, $options);
    $this->value = array($options['access_id']);
  }

  function option_definition() {
    $options = parent::option_definition();
    $options['access_id'] = array('default' => NULL);
    $options['size'] = array('default' => 5);
    return $options;
  }

  function operator_options($which = 'title') {
    return array(
      'or' => t('Is one of'),
    );
  }

  function get_value_options() {
    $active = workbench_access_get_active_tree();
    $tree = $active['tree'];
    workbench_access_build_tree($tree, array_keys($active['active']));
    return workbench_access_options($tree, $active['active']);
  }

  function value_form(&$form, &$form_state) {
    $options = $this->get_value_options();
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

  function value_submit($form, &$form_state) {
    $this->value = $form_state['values']['access_id'];
  }

  function exposed_form(&$form, &$form_state) {
    // Prevent parent form errors by using a value.
    $form['value'] = array('#type' => 'value', '#value' => '');
    parent::exposed_form($form, $form_state);
    // Build our form element.
    $active = workbench_access_get_active_tree();
    $tree = workbench_access_get_user_tree();
    $options = workbench_access_options($tree, $active['active']);
    $form['access_id'] = array(
      '#type' => 'select',
      '#multiple' => TRUE,
      '#options' => $options,
      '#default_value' => $this->options['access_id'],
      '#size' => $this->options['size'],
    );
    unset($form['size']);
  }

  function exposed_submit(&$form, &$form_state) {
    if (empty($form_state['values']['access_id'])) {
      $form_state['values']['access_id'] = -5;
    }
  }

  function query() {
    global $user;
    static $node_types;

    // If workbench_access is not configured, do nothing.
    $active = workbench_access_get_active_tree();
    $access_table = $active['access_scheme']['field_table'];
    $tree = $active['tree'];
    if (empty($tree)) {
      return;
    }

    // Check the user's access.
    $account = $user; // Not a clone, but that's ok, since we need this data on $user.

    // Load workbench_access user data onto the account object.
    if (!isset($account->workbench_access)) {
      workbench_access_user_load_data($account);
    }

    // Get node types that do not use workbench_access for access control.
    $node_types = array();
    foreach (node_type_get_names() as $type => $name) {
      if (!variable_get('workbench_access_node_type_' . $type, TRUE)) {
        $node_types[$type] = $type;
      }
    }

    // Here we start altering the query. All of our alters should be their own
    // where group.
    $group = $this->query->set_where_group('OR');

    // If the user has no workbench_access rights, and all node types use
    // workbench_access, force the query to return nothing.
    if (empty($account->workbench_access) && empty($node_types)) {
      $table = $this->view->base_table;
      $this->query->add_where($group, "$table.nid", -1, '=');
      return;
    }

    // If there is no selection, use the user's own access tree.
    if (empty($this->value) || $this->value[0] == -5) {
      workbench_access_build_tree($tree, array_keys($account->workbench_access));
    }
    else {
      workbench_access_build_tree($tree, array_keys($this->value[0]));
    }

    // Build the workbench_access where clause.
    if (!empty($tree)) {
      // Since we allow multi-select, this has to be a subquery.
      $ids = array_keys($tree);
      $table = $active['access_scheme']['field_table'];
      $subquery = db_select($table, $table);
      $subquery->addField($table, 'nid');
      $subquery->distinct();
      $subquery->condition($table . '.' . $active['access_scheme']['query_field'], $ids, 'IN');
      $subquery->condition($table . '.access_scheme', $active['access_scheme']['access_scheme']);

      $this->query->add_where($group, "{$this->query->base_table}.nid", $subquery, 'IN');
    }

    // If not all node types use workbench access for permissions, add them here.
    if (!empty($node_types)) {
      $this->query->add_where($group, "{$this->query->base_table}.type", $node_types, 'IN');
    }
  }
}
