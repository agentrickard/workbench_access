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

  /**
   * Test that the Workbench Access setting applies properly.
   */
  public function testWorkbenchAccessNodeTypeForm() {

    // Get the page content type settings.

    // Workbench Access should not be visible.

    // Set permission to 'administer workbench access'.

    // Workbench Access should be visible.

    // Save the form.

    // Test the setting in the form.

    // Test the setting on the entity.
  }
}
