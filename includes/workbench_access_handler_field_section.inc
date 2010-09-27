<?php

class workbench_access_handler_field_section extends views_handler_field {
  // No query for this field.
  function query() {
    $active = workbench_access_get_active_tree();
    $field_table = $this->query->ensure_table($active['access_scheme']['field_table']);
    // Using this field alias breaks the COUNT query. Views bug?
    $field = $this->query->add_field($field_table, $active['access_scheme']['query_field'], 'workbench_access_id');
  }
  
  function click_sort($order) {
    $active = workbench_access_get_active_tree();
    if (empty($active['access_scheme']['sort'])) {
      return;
    }
    foreach ($active['access_scheme']['sort'] as $sort) {
      $table = $this->query->ensure_table($sort['table']);
      $this->query->add_orderby($table, $sort['field'], (isset($sort['order'])) ? $sort['order'] : $order);
    }
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
    $active = workbench_access_get_active_tree();
    $name = '';
    $tree = $active['tree'];
    workbench_access_build_tree($tree, array_keys($account->workbench_access));
    if (isset($account->workbench_access[$values->workbench_access_id])) {
      $name = $tree[$values->workbench_access_id]['name'];
    }
    else {
      foreach ($account->workbench_access as $id => $data) {
        if (!empty($tree[$id]['children'])) {
          $children = $tree[$id]['children'];
          if (in_array($values->workbench_access_id, $children)) {
            $name = $tree[$id]['name'] . ' > ' . $tree[$values->workbench_access_id]['name'];
          }
        }
      }
    }
    return check_plain($name);
  }
}
