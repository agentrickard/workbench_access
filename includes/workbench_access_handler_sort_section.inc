<?php

class workbench_access_handler_sort_section extends views_handler_sort {
  function query() {
    // TODO: Clean this up.
    $scheme = variable_get('workbench_access', 'taxonomy');
    $func = $scheme . '_workbench_access_info';
    $info = $func();
    $data = $info[$scheme];
    if (empty($data['sort'])) {
      return;
    }
    $order = $this->options['order'];
    foreach ($data['sort'] as $sort) {
      $table = $this->query->ensure_table($sort['table']);
      $this->query->add_orderby($table, $sort['field'], (isset($sort['order'])) ? $sort['order'] : $order);
    }
  }
}
