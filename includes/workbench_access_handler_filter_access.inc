<?php

class workbench_access_handler_filter_access extends views_handler_filter {
  function option_definition() {
    $options = parent::option_definition();
    $options['access_id'] = array('default' => array());
    return $options;
  }

  function operator_options() {
    return array(
      'or' => t('Is one of'),
    );
  }

  function value_form(&$form, &$form_state) {
   # $options = array(-5 => t('All sections'));
    // TODO: Clean this up.
    $scheme = variable_get('workbench_access', 'taxonomy');
    $func = $scheme . '_workbench_access_info';
    $info = $func();
    $data = $info[$scheme];
    $tree = workbench_access_tree($data);
    $active = workbench_access_get_ids_by_scheme($data);
    workbench_access_build_tree($tree, array_keys($active));
    $options = workbench_access_options($tree);
    $form['access_id'] = array(
      '#type' => 'select',
      '#title' => t('Sections'),
      '#multiple' => TRUE,
      '#options' => $options,
      '#default_value' => $this->options['access_id'],
      '#size' => 5,
    );  
  }

  function exposed_form(&$form, &$form_state) {
    parent::exposed_form(&$form, &$form_state);
    global $user;
    $account = $user;
    if (!isset($account->workbench_access)) {
      workbench_access_load_user_data($account);
    }
    $scheme = variable_get('workbench_access', 'taxonomy');
    $func = $scheme . '_workbench_access_info';
    $info = $func();
    $data = $info[$scheme];
    $tree = workbench_access_tree($data);
    workbench_access_build_tree($tree, array_keys($account->workbench_access));
    unset($form['access_id']['#title']);
    foreach ($form['access_id']['#options'] as $access_id => $name) {
      if ($access_id != -5 && !isset($tree[$access_id])) {
        unset($form['access_id']['#options'][$access_id]);
      }
    }
  }
  
  function expose_submit($form, &$form_state) {
    dsm($form_state);
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
    // TODO: Clean this up.
    $scheme = variable_get('workbench_access', 'taxonomy');
    $func = $scheme . '_workbench_access_info';
    $info = $func();
    $data = $info[$scheme];
    $active = workbench_access_get_ids_by_scheme($data);
    $tree = workbench_access_tree($data);
    // No selection? Use the user's tree.
    if (empty($this->value)) {
      workbench_access_build_tree($tree, array_keys($account->workbench_access));
    }
    // Build the selection tree.
    else {
      workbench_access_build_tree($tree, array_keys($this->value));
    }
    $ids = array_keys($tree);
    $table = $this->query->add_table($data['node_table']);
    // Using this field alias breaks the COUNT query. Views bug?
    $field = $this->query->add_field($table, $data['query_field']);
    $this->query->add_where($this->options['group'], "$table." . $data['query_field'], $ids, 'IN');
  }
}
