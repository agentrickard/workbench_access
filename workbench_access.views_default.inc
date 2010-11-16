<?php

/**
 * @file
 *  Views integration for Workbench.
 *
 * @copyright (C) Copyright 2010 Palantir.net
 */

/**
 * Implementation of hook_views_default_views().
 */
function workbench_access_views_default_views() {  
  // Find all the .view files in the views subdirectory that end in .view.
  $files = file_scan_directory(drupal_get_path('module', 'workbench_access') . '/views', '/.view.inc/');

  $views = array();
  foreach ($files as $path => $file) {
    require $path;
    if (isset($view)) {
      $views[$view->name] = $view;
    }
  }
  return $views;
}
