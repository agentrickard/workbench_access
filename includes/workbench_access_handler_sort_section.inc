<?php

/**
 * @file
 *  Views integration for Workbench.
 *
 * @copyright (C) Copyright 2010 Palantir.net
 */

class workbench_access_handler_sort_section extends views_handler_sort {
  function query() {
    $active = workbench_access_get_active_tree();
    if (empty($active['access_scheme']['sort'])) {
      return;
    }
    $order = $this->options['order'];
    foreach ($active['access_scheme']['sort'] as $sort) {
      $table = $this->query->ensure_table($sort['table']);
      $this->query->add_orderby($table, $sort['field'], (isset($sort['order'])) ? $sort['order'] : $order);
    }
    // Logic to remove JOIN to the default table, which causes duplicate results.
    if (isset($active['access_scheme']['adjust_join'])) {
      foreach ($active['access_scheme']['adjust_join'] as $table => $replace) {
        $join = $this->query->get_join_data($table, 'node');
        if ($join->left_table == $replace['original_table']) {
          $this->query->table_queue[$table]['join']->left_table = $replace['new_table'];
          $this->query->table_queue[$table]['join']->left_field = $replace['new_field'];
          unset($this->query->tables['node'][$replace['original_table']]);
          unset($this->query->table_queue[$replace['original_table']]);
        }
      }
    }
  }
}
