<?php

namespace Drupal\Tests\workbench_access\Functional;

use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\Tests\BrowserTestBase;
use Drupal\workbench_access\WorkbenchAccessManagerInterface;

/**
 * Tests for the node form.
 *
 * @group workbench_access
 */
class NodeFormMenuTest extends BrowserTestBase {

  use WorkbenchAccessTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'workbench_access',
    'node',
    'menu_ui',
    'link',
    'menu_link_content',
    'options',
    'user',
    'system',
  ];

  /**
   * Tests that the user can see all valid options on the node form.
   */
  public function testNodeForm() {
    // Set up a content type and menu scheme.
    $node_type = $this->createContentType(['type' => 'page']);
    $scheme = $this->setUpMenuScheme(['page'], ['main']);
    // Set up an editor and log in as them.
    $editor = $this->setUpEditorUser();
    $this->drupalLogin($editor);

    // Set up some roles and menu links for this test.
    $staff_link = MenuLinkContent::create([
      'title' => 'Link 1',
      'link' => [['uri' => 'route:<front>']],
      'menu_name' => 'main',
    ]);
    $staff_link->save();
    $super_staff_link = MenuLinkContent::create([
      'title' => 'Link 2',
      'link' => [['uri' => 'route:<front>']],
      'menu_name' => 'main',
    ]);
    $super_staff_link->save();
    $base_link = MenuLinkContent::create([
      'title' => 'Link 3',
      'link' => [['uri' => 'route:<front>']],
      'menu_name' => 'main',
    ]);
    $base_link->save();
    $editor->{WorkbenchAccessManagerInterface::FIELD_NAME} = 'editorial_section:' . $base_link->getPluginId();
    $editor->save();

    $staff_rid = $this->createRole([], 'staff');
    $super_staff_rid = $this->createRole([], 'super_staff');
    // Set the role -> term mapping.
    \Drupal::service('workbench_access.role_section_storage')->addRole($scheme, $staff_rid, [$staff_link->getPluginId()]);
    \Drupal::service('workbench_access.role_section_storage')->addRole($scheme, $super_staff_rid, [$super_staff_link->getPluginId()]);

    $web_assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    // Assert we can't see the options yet.
    $web_assert->optionNotExists('menu[menu_parent]', $staff_link->label());
    $web_assert->optionNotExists('menu[menu_parent]', $super_staff_link->label());

    // Add the staff role and check the option exists.
    $editor->addRole($staff_rid);
    $editor->save();
    $this->drupalGet('node/add/page');
    $web_assert->optionExists('menu[menu_parent]', $staff_link->label());

    // Add the super staff role and check both options exist.
    $editor->addRole($super_staff_rid);
    $editor->save();
    $this->drupalGet('node/add/page');
    $web_assert->optionExists('menu[menu_parent]', $staff_link->label());
    $web_assert->optionExists('menu[menu_parent]', $super_staff_link->label());
  }

}
