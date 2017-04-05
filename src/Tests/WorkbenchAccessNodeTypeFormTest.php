<?php

namespace Drupal\workbench_access\Tests;

use Drupal\workbench_access\Tests\WorkbenchAccessTestBase;
use Drupal\node\NodeTypeInterface;

/**
 * Adds configuration options to node types.
 *
 * @group workbench_access
 */
class WorkbenchAccessNodeTypeFormTest extends WorkbenchAccessTestBase {

  /**
   * An editor user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $editor;

  protected function setUp() {
    parent::setUp();

    $this->editor = $this->drupalCreateUser(array(
      'access administration pages',
      'administer content types',
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
    // The form text string to find.
    $string = 'Enable Workbench Access control for Basic page content.';
    // The form path.
    $path = 'admin/structure/types/manage/page';

    // Get the page content type settings.
    $this->drupalGet($path);

    // Workbench Access should not be visible.
    $this->assertNoText($string);

    // Set permission to 'administer workbench access'.
    user_role_grant_permissions(DRUPAL_AUTHENTICATED_RID, array('administer workbench access'));

    // Workbench Access should be visible.
    $this->drupalGet('admin/structure/types/manage/page');
    $this->assertText($string);

    // Test the setting on the entity.
    $type = entity_load('node_type', 'page');
    $status = $type->getThirdPartySetting('workbench_access', 'workbench_access_status', 0);
    $this->assertTrue($status === 0, 'Access control status is set to zero (off).');

    // Save the form.
    $edit['workbench_access_status'] = 1;
    $this->drupalPostForm($path, $edit, 'Save content type');

    // Test the setting on the entity.
    $type = entity_load('node_type', 'page');
    $status = $type->getThirdPartySetting('workbench_access', 'workbench_access_status', 0);
    $this->assertTrue($status == 1, 'Access control status is set to one (on).');

  }
}
