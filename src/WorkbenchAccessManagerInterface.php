<?php

/**
 * @file
 * Contains \Drupal\workbench_access\WorkbenchAccessManagerInterface.
 */

namespace Drupal\workbench_access;

interface WorkbenchAccessManagerInterface {

  public function getSchemes();
  public function getScheme($id);
  public function getActiveScheme();
  public function getActiveTree();
  public function getElement($id);
  public function getDefaultValue();

}
