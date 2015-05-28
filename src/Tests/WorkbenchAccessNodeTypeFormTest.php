<?php

/**
 * @file
 * Contains \Drupal\workbench_access\Tests\WorkbenchAccessNodeTypeFormTest
 */

namespace Drupal\workbench_access\Tests;

use Drupal\simpletest\WebTestBase;

class WorkbenchAccessNodeTypeFormTest extends WebTestBase {

  /**
   * An editor user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $editor;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('workbench_access', 'node');

  protected function setUp() {
    parent::setUp();

    $this->drupalCreateContentType(array('type' => 'page', 'name' => 'Basic page'));

    $this->editor = $this->drupalCreateUser(array(
      'access administration pages',
      'administer content types',
      'administer menu',
      'create page content',
      'edit any page content',
      'delete any page content',
    ));
    $this->drupalLogin($this->editor);
  }

}
