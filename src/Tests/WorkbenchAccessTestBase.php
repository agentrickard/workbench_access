<?php

/**
 * @file
 * Contains \Drupal\workbench_access\Tests\WorkbenchAccessTestBase
 */

namespace Drupal\workbench_access\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Base test class for Workbench Access.
 */
abstract class WorkbenchAccessTestBase extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('workbench_access', 'node', 'taxonomy', 'menu_ui', 'options');

  /**
   * The Workbench Access manager class.
   *
   * @var Drupal\workbench_access\WorkbenchAccessManager
   */
  protected $manager;

  protected function setUp() {
    parent::setUp();

    $this->drupalCreateContentType(array('type' => 'article', 'name' => 'Article'));
    $this->drupalCreateContentType(array('type' => 'page', 'name' => 'Basic page'));

    // Install our base taxonomy.
    $this->installTaxonomy();

    // Install a base menu.
    $this->installMenu();

    // Instantiate the manager.
    $this->manager = \Drupal::getContainer()->get('plugin.manager.workbench_access.scheme');

  }

  /**
   * Loads and installs the test taxonomy from drush.
   */
  protected function installTaxonomy() {
    $file = DRUPAL_ROOT . '/' . drupal_get_path('module', 'workbench_access') . "/workbench_access.drush.inc";
    require_once $file;
    drush_workbench_access_test();
  }

  /**
   * Loads and installs the test menu.
   */
  protected function installMenu() {
    // @TODO.
  }

}
