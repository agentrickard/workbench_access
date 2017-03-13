<?php

namespace Drupal\Tests\workbench_access\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Defines a class for testing the configuration form.
 *
 * @group workbench_access
 */
class ConfigFormTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'workbench_access',
    'menu_ui',
    'taxonomy',
    'system',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // Login the admin user.
    $this->drupalLogin($this->createUser([], NULL, TRUE));
  }

  /**
   * Tests the config form.
   */
  public function testConfigFormSubmit() {
    $assert = $this->assertSession();
    $this->drupalGet('admin/config/workflow/workbench_access');
    $assert->statusCodeEquals(200);
    // Check the two schemes exist.
    $page = $this->getSession()->getPage();
    $menu_field = $page->find('css', 'input[name=scheme][value=menu]');
    $this->assertNotNull($menu_field);
    $this->assertNotNull($page->find('css', 'input[name=scheme][value=taxonomy]'));
    $page->fillField('scheme', $menu_field->getAttribute('value'));
    $this->submitForm([], 'Save configuration');
    $assert->statusCodeEquals(200);
    $scheme = $this->container->get('plugin.manager.workbench_access.scheme')->getActiveScheme();
    $this->assertEquals('menu', $scheme->id());
  }

}
