<?php

namespace Drupal\Tests\workbench_access\Functional;

use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\taxonomy\Entity\Term;
use Drupal\Tests\BrowserTestBase;
use Drupal\workbench_access\Entity\AccessSchemeInterface;
use Drupal\Tests\workbench_access\Traits\WorkbenchAccessTestTrait;

/**
 * Tests for the access by user form.
 *
 * @group workbench_access
 */
class AccessByUserFormTest extends BrowserTestBase {

  use WorkbenchAccessTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'workbench_access',
    'node',
    'taxonomy',
    'options',
    'user',
    'system',
    'link',
    'menu_ui',
    'menu_link_content',
  ];

  /**
   * Tests that the correct users are displayed on the access by user form.
   */
  public function testAccessByUserForm() {
    $node_type = $this->createContentType(['type' => 'page']);
    $vocab = $this->setUpVocabulary();
    $this->setUpTaxonomyFieldForEntityType('node', $node_type->id(), $vocab->id());
    $scheme = $this->setUpTaxonomyScheme($node_type, $vocab);

    // Set up some roles and terms for this test.
    $staff_term = Term::create([
      'vid' => $vocab->id(),
      'name' => 'Staff',
    ]);
    $staff_term->save();
    $section_id = $staff_term->id();
    $this->doFormTests($scheme, $section_id, 'Staff');
  }

  /**
   * Tests that the correct users are displayed on the access by user form.
   */
  public function testAccessByUserFormMenu() {
    // Set up test scheme.
    $node_type = $this->createContentType(['type' => 'page']);
    $scheme = $this->setUpMenuScheme([$node_type->id()], ['main']);

    // Create a menu link.
    $link = MenuLinkContent::create([
      'title' => 'Home',
      'link' => [['uri' => 'route:<front>']],
      'menu_name' => 'main',
    ]);
    $link->save();
    $section_id = $link->id();
    $this->doFormTests($scheme, $section_id, 'Home');
  }

  /**
   * Test the form with the given section.
   *
   * @param \Drupal\workbench_access\Entity\AccessSchemeInterface $scheme
   *   The access scheme.
   * @param string $section_id
   *   Section ID.
   * @param string $section_label
   *   Section label.
   */
  protected function doFormTests(AccessSchemeInterface $scheme, $section_id, $section_label) {
    $web_assert = $this->assertSession();
    $page = $this->getSession()->getPage();
    $user_storage = $this->container->get('workbench_access.user_section_storage');

    $non_staff_rid = $this->createRole([], 'non_staff');
    $staff_rid = $this->createRole(['use workbench access'], 'staff');

    $user1 = $this->createUserWithRole($non_staff_rid);
    $user2 = $this->createUserWithRole($staff_rid);
    $user3 = $this->createUserWithRole($staff_rid);
    $user4 = $this->createUserWithRole($staff_rid);

    $this->drupalLogin($this->setUpAdminUser());
    $this->drupalGet('/admin/config/workflow/workbench_access/editorial_section/sections');
    $web_assert->pageTextContains('Editorial sections');
    $web_assert->pageTextContains($section_label);
    $this->drupalGet(sprintf('/admin/config/workflow/workbench_access/editorial_section/sections/%s/users', $section_id));

    // Add a user from staff with autocomplete.
    $page->fillField('edit-editors-add', $user2->label() . ' (' . $user2->id() . ')');
    $page->pressButton('add');

    // We expect to find user 2 in the active list.
    $expected = [$user2->id()];
    $existing_users = $user_storage->getEditors($scheme, $section_id);
    $this->assertEquals($expected, array_keys($existing_users));

    // Check remove editors list.
    $editors = $page->findField('editors_remove');
    $web_assert->fieldNotExists('editors_remove[' . $user1->id() . ']', $editors);
    $web_assert->fieldExists('editors_remove[' . $user2->id() . ']', $editors);

    // Test remove the user.
    $page->checkField('editors_remove[' . $user2->id() . ']');
    $page->pressButton('remove');

    // We expect to find no users in the active list.
    $expected = [];
    $existing_users = $user_storage->getEditors($scheme, $section_id);
    $this->assertEquals($expected, array_keys($existing_users));

    // Check user has been removed to the section.
    $editors = $page->findField('editors_remove');
    $web_assert->fieldNotExists('editors_remove[' . $user2->id() . ']', $editors);

    // Test adding users with the textarea, mixed username and uid.
    $page->fillField('edit-editors-add-mass', $user3->label() . ', ' . $user4->id());
    $page->pressButton('add');

    // We expect to find users 3 and 4 in the active list.
    $expected = [$user3->id(), $user4->id()];
    $existing_users = $user_storage->getEditors($scheme, $section_id);
    $this->assertEquals($expected, array_keys($existing_users));

    $editors = $page->findField('editors_remove');
    $web_assert->fieldExists('editors_remove[' . $user3->id() . ']', $editors);
    $web_assert->fieldExists('editors_remove[' . $user4->id() . ']', $editors);

  }

}
