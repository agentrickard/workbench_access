<?php

/**
 * @file
 * Contains \Drupal\workbench_access\Controller\WorkbenchAccessSections.
 */

namespace Drupal\workbench_access\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\workbench_access\WorkbenchAccessManagerInterface;

/**
 * Generates the sections list page.
 */
class WorkbenchAccessSections extends ControllerBase {

  public function page() {
    $config = $this->config('workbench_access.settings');
    $scheme_id = $config->get('scheme');
    $this->manager = \Drupal::getContainer()->get('plugin.manager.workbench_access.scheme');
    $scheme = $this->manager->getScheme($scheme_id);
    $parents = $config->get('parents');
    $tree = $scheme->getTree();
    dpm($tree);
    return array('foo');
  }
}
