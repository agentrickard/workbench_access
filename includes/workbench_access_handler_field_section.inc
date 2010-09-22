<?php

class workbench_access_handler_field_section extends views_handler_field {
  // No query for this field.
  function query() {
    // TODO: Clean this up.
    $scheme = variable_get('workbench_access', 'taxonomy');
    $func = $scheme . '_workbench_access_info';
    $info = $func();
    $data = $info[$scheme];
    $table = $this->query->add_table($data['node_table']);
    $field_table = $this->query->add_table($data['field_table']);
    // Using this field alias breaks the COUNT query. Views bug?
    $field = $this->query->add_field($field_table, $data['query_field'], 'workbench_access_id');
  }
  
  function render($values) {
    global $user;
    $account = $user;
    if (!isset($account->workbench_access)) {
      workbench_access_load_user_data($account);
    }
    if (!isset($values->workbench_access_id)) {
      return '';
    }
    // TODO: Clean this up.
    $scheme = variable_get('workbench_access', 'taxonomy');
    $func = $scheme . '_workbench_access_info';
    $info = $func();
    $data = $info[$scheme];
    $tree = workbench_access_tree($data);
    $name = '';
    workbench_access_build_tree($tree, array_keys($account->workbench_access));
    if (isset($account->workbench_access[$values->workbench_access_id])) {
      $name = $tree[$values->workbench_access_id]['name'];
    }
    else {
      foreach ($account->workbench_access as $id => $data) {
        $children = $tree[$id]['children'];
        if (in_array($values->workbench_access_id, $children)) {
          $name = $tree[$id]['name'] . ' > ' . $tree[$values->workbench_access_id]['name'];
        }
      }
    }
    return check_plain($name);
  }
}
