<?php

namespace Drupal\Tests\workbench_access_protect\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\workbench_access\Traits\WorkbenchAccessTestTrait;
use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\system\Entity\Menu;
use Drupal\workbench_access\WorkbenchAccessManagerInterface;
use Drupal\workbench_access\Entity\AccessSchemeInterface;

/**
 * Tests protection of Menu used for access control.
 *
 * @group workbench_access_protect
 */
class MenuProtectTest extends BrowserTestBase {

  use WorkbenchAccessTestTrait;

  /**
   * Admin user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $admin;

  /**
   * Admin user, without ability to delete terms.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $editor;

  /**
   * Menu used for access control.
   *
   * @var \Drupal\system\Entity\Menu
   */
  protected $menu;

  /**
   * Menu not used for access control.
   *
   * @var \Drupal\system\Entity\Menu
   */
  protected $emptyMenu;

  /**
   * A test menu link.
   *
   * @var \Drupal\menu_link_content\Entity\MenuLinkContentInterface
   */
  protected $link;

  /**
   * A test menu that is never tagged in content.
   *
   * @var \Drupal\menu_link_content\Entity\MenuLinkContentInterface
   */
  protected $emptyLink;

  /**
   * A test term that is never tagged in content.
   *
   * @var \Drupal\menu_link_content\Entity\MenuLinkContentInterface
   */
  protected $emptyMenuLink;

  /**
   * The test access scheme.
   *
   * @var \Drupal\workbench_access\Entity\AccessSchemeInterface
   */
  protected $scheme;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'menu_link_content',
    'menu_ui',
    'node',
    'options',
    'system',
    'user',
    'workbench_access',
    'workbench_access_protect',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // Set up a content type, taxonomy field, and taxonomy scheme.
    $node_type = $this->createContentType(['type' => 'page']);
    $this->scheme = $this->setUpMenuScheme(['page'], ['main']);

    // Set up a menu for access control.
    $this->menu = Menu::create([
      'id' => 'menu_test',
      'label' => 'Test menu',
      'description' => 'Description text',
    ])->save();
    // Set up an non-access control menu as a control.
    $this->emptyMenu = Menu::create([
      'id' => 'empty_test',
      'label' => 'Empty menu',
      'description' => 'Description text',
    ])->save();

    // Allow nodes to be assigned to the menu.
    $node_type->setThirdPartySetting('menu_ui', 'available_menus', ['menu_test', 'empty_test']);
    $node_type->save();

    // Set up some menu links for this test.
    $this->link = MenuLinkContent::create([
      'title' => 'Link 1',
      'link' => [['uri' => 'route:<front>']],
      'menu_name' => 'menu_test',
    ]);
    $this->link->save();
    $this->emptyLink = MenuLinkContent::create([
      'title' => 'Link 2',
      'link' => [['uri' => 'route:<front>']],
      'menu_name' => 'menu_test',
    ]);
    $this->emptyLink->save();
    $this->emptyMenuLink = MenuLinkContent::create([
      'title' => 'Link 3',
      'link' => [['uri' => 'route:<front>']],
      'menu_name' => 'empty_test',
    ]);
    $this->emptyMenuLink->save();

    // Create users.
    $this->admin = $this->setUpAdminUser([
      'administer workbench access',
      'administer menu',
      'link to any page'
    ]);
    $this->editor = $this->setUpUserUniqueRole([
      'administer workbench access',
      'assign workbench access',
      'administer menu',
      'link to any page'
    ]);
  }

  /**
   * Creates content for the test.
   */
  protected function setUpTestContent() {
    $testNode = $this->createNode(
      [
        'title' => 'Node',
        'type' => 'page',
        'uid' => $this->admin->id(),
      ]
    );
    _menu_ui_node_save($testNode, [
      'title' => 'foo',
      'menu_name' => 'menu_test',
      'description' => 'view foo',
      'entity_id' => $this->link->id(),
      'parent' => NULL,
    ]);
    $testNode2 = $this->createNode(
      [
        'title' => 'Node 2',
        'type' => 'page',
        'uid' => $this->admin->id(),
      ]
    );
    _menu_ui_node_save($testNode2, [
      'title' => 'bar',
      'menu_name' => 'menu_test',
      'description' => 'view bar',
      'entity_id' => $this->emptyLink->id(),
      'parent' => NULL,
    ]);
    $testNode3 = $this->createNode(
      [
        'title' => 'Node 3',
        'type' => 'page',
        'uid' => $this->admin->id(),
      ]
    );
    _menu_ui_node_save($testNode3, [
      'title' => 'baz',
      'menu_name' => 'empty_test',
      'description' => 'view baz',
      'entity_id' => $this->emptyMenuLink->id(),
      'parent' => NULL,
    ]);
  }

  /**
   * Assigns users for the test.
   */
  protected function setUpTestUser() {
    $user_storage = \Drupal::service('workbench_access.user_section_storage');
    // Add the user to the base section.
    $user_storage->addUser($this->scheme, $this->editor, [$this->link->getPluginId()]);
    $expected = [$this->editor->id()];
    $existing_users = $user_storage->getEditors($this->scheme, $this->link->getPluginId());
    $this->assertEquals($expected, array_keys($existing_users));
  }

  /**
   * Assert a non-administrator cannot delete terms used by nodes.
   */
  public function testCannotDeleteMenuWithAssignedNode() {
    $this->setUpTestContent();
    $this->assertTests();
  }

  /**
   * Assert a non-administrator cannot delete terms used by users.
   */
  public function testCannotDeleteMenuWithAssignedUser() {
    $this->setUpTestUser();
    $this->assertTests();
  }

  /**
   * Runs our tests for both content and users.
   */
  private function assertTests() {
    // Login to the non-privileged account.
    $this->drupalLogin($this->editor);

// overview: /admin/structure/menu/manage/menu_test
// delete: /admin/structure/menu/manage/menu_test/delete
// overview: /admin/structure/menu/manage/empty_test
// delete: /admin/structure/menu/manage/empty_test/delete
// link: /admin/structure/menu/item/1/edit
// Link: /admin/structure/menu/item/1/delete


    // Restricted link that has content.
    $path = '/admin/structure/menu/item/' . $this->link->id()  . '/edit';
    $this->drupalGet($path);
    $delete_path = '/admin/structure/menu/item/' . $this->link->id() . '/delete';
    $this->assertSession()->linkByHrefNotExists($delete_path);
    $this->drupalGet($delete_path);
    $this->assertSession()->statusCodeEquals(403);

    // Unrestricted link that has no content.
    $path = '/admin/structure/menu/item/' . $this->emptyLink->id()  . '/edit';
    $this->drupalGet($path);
    $delete_path = '/admin/structure/menu/item/' . $this->emptyLink->id() . '/delete';
    $this->assertSession()->linkByHrefExists($delete_path);
    $this->drupalGet($delete_path);
    $this->assertSession()->statusCodeEquals(200);

    // Unrestricted link in a menu that has no content.
    $path = '/admin/structure/menu/item/' . $this->emptyMenuLink->id()  . '/edit';
    $this->drupalGet($path);
    $delete_path = '/admin/structure/menu/item/' . $this->emptyMenuLink->id() . '/delete';
    $this->assertSession()->linkByHrefExists($delete_path);
    $this->drupalGet($delete_path);
    $this->assertSession()->statusCodeEquals(200);

    // Test for a delete menu page.
    $menu_path = '/admin/structure/menu/manage/' . $this->menu->id() . '/delete';
    $this->drupalGet($menu_path);
    $this->assertSession()->statusCodeEquals(403);
    $delete_path = '/admin/structure/menu/manage/' . $this->emptyMenu->id() . '/delete';
    $this->drupalGet($delete_path);
    $this->assertSession()->statusCodeEquals(200);

    // Test the overview page to make sure that the delete link is handled.
    $menu_path = '/admin/structure/menu/manage/' . $this->menu->id();
    $this->drupalGet($menu_path);
    $delete_path = '/admin/structure/menu/manage/' . $this->menu->id() . '/delete';
    $this->assertSession()->linkByHrefNotExists($delete_path);

    // Test links on this page.
    $delete_path = '/admin/structure/menu/item/' . $this->link->id() . '/delete';
    $this->assertSession()->linkByHrefNotExists($delete_path);
    $delete_path = '/admin/structure/menu/item/' . $this->emptyLink->id() . '/delete';
    $this->assertSession()->linkByHrefExists($delete_path);

    $menu_path = '/admin/structure/menu/manage/' . $this->emptyMenu->id();
    $this->drupalGet($menu_path);
    $delete_path = '/admin/structure/menu/manage/' . $this->emptyMenu->id() . '/delete';
    $this->assertSession()->linkByHrefExists($delete_path);

    // Test links on this page.
    $delete_path = '/admin/structure/menu/item/' . $this->emptyMenuLink->id() . '/delete';
    $this->assertSession()->linkByHrefExists($delete_path);
  }

}
