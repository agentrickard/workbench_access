<?php

namespace Drupal\workbench_access\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\workbench_access\WorkbenchAccessManagerInterface;

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
   * @var \Drupal\workbench_access\WorkbenchAccessManager
   */
  protected $pluginmanager;

  /**
   * The entity storage manager.
   */
  protected $storage;

  /**
   * An array of created nodes.
   */
  protected $nodes;

  protected function setUp() {
    parent::setUp();

    $this->drupalCreateContentType(array('type' => 'article', 'name' => 'Article'));
    $this->drupalCreateContentType(array('type' => 'page', 'name' => 'Basic page'));

    // Install our base taxonomy.
    $this->installTaxonomy();

    // Install our base nodes for testing.
    $this->installNodes();

    // Install a base menu.
    $this->installMenu();

    // Instantiate the manager.
    $this->manager = \Drupal::getContainer()->get('plugin.manager.workbench_access.scheme');
    $this->assertTrue(count($this->nodes) == 12, '12 nodes created');
    $tree = $this->manager->getActiveTree();
    $this->assertTrue(count($tree['workbench_access']) == 13, 'Access tree has 13 items');
  }

  /**
   * Loads and installs the test taxonomy from drush.
   */
  protected function installTaxonomy() {
    // This creates 12 taxonomy terms.
    $file = DRUPAL_ROOT . '/' . drupal_get_path('module', 'workbench_access') . "/workbench_access.drush.inc";
    require_once $file;
    drush_workbench_access_test();
  }

  /**
   * Loads and installs the test menu.
   */
  protected function installMenu() {
    // We have 12 nodes. They should mirror the taxonomy tree.
    // @TODO.
  }

  protected function installNodes() {
    // Create nine nodes, each assigned to a taxonomy term.
    // Terms 11 and 12 will have no assignees.
    for ($i = 1; $i <= 10; $i++) {
      $this->nodes[] = $this->drupalCreateNode(array(
        'type' => 'article',
        WorkbenchAccessManagerInterface::FIELD_NAME => array($i),
      ));
    }
    // Create two page nodes for testing.
    for ($i = 1; $i <= 2; $i++) {
      $this->nodes[] = $this->drupalCreateNode(array(
        'type' => 'page',
      ));
    }
  }

  protected function setTaxonomyScheme() {
    // @TODO: switch to taxonomy scheme.
  }

  protected function setMenuScheme() {
    // @TODO: switch to menu scheme.
  }


}
