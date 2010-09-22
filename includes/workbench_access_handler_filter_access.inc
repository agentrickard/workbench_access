<?php

class workbench_access_handler_filter_access extends views_handler_filter {
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
    workbench_build_tree($tree, array_keys($account->workbench_access));
    $ids = array_keys($tree);
    $table = $this->query->add_table($data['node_table']);
    // Using this field alias breaks the COUNT query. Views bug?
    $field = $this->query->add_field($table, $data['query_field']);
    $this->query->add_where($this->options['group'], "$table." . $data['query_field'], $ids, 'IN');
  }
}
