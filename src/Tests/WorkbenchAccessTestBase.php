<?php

/**
 * @file
 * Contains \Drupal\workbench_access\Tests\WorkbenchAccessTestBase
 */

namespace Drupal\workbench_access\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Base test class for Workbench Access.
 *
 * @group workbench_access
 */
class WorkbenchAccessTestBase extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('workbench_access', 'node', 'taxonomy', 'options');

  protected function setUp() {
    parent::setUp();

    $this->drupalCreateContentType(array('type' => 'page', 'name' => 'Basic page'));

    // Install our base taxonomy.
    module_load_include('inc', 'workbench_access', 'drush');
    drush_workbench_access_test();

    // Install a test menu.

  }

}
