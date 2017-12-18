<?php

namespace Drupal\Tests\workbench_access\Functional;

use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\taxonomy\Entity\Term;
use Drupal\Tests\BrowserTestBase;
use Drupal\workbench_access\RoleSectionStorageInterface;

/**
 * Tests for the access by role form.
 *
 * @group workbench_access
 */
class AccessByRoleFormTest extends BrowserTestBase {

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
    'link',
    'menu_ui',
    'menu_link_content',
    'system',
  ];

  /**
   * Tests that the correct roles are displayed on the access by role form.
   */
  public function testAccessByRoleForm() {
    // Set up a content type, taxonomy field, and taxonomy scheme.
    $node_type = $this->createContentType(['type' => 'page']);
    $vocab = $this->setUpVocabulary();
    $this->setUpTaxonomyFieldForEntityType('node', $node_type->id(), $vocab->id());
    $scheme = $this->setUpTaxonomyScheme($node_type, $vocab);

    // Set up some roles and terms for this test.
    // Create terms and roles.
    $staff_term = Term::create([
      'vid' => $vocab->id(),
      'name' => 'Staff',
    ]);
    $staff_term->save();
    $section_id = $staff_term->id();
    $this->doFormTests($section_id);
  }

  /**
   * Tests that the correct roles are displayed on the access by role form.
   */
  public function testAccessByRoleFormMenu() {
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
    $section_id = $link->getPluginId();
    $this->doFormTests($section_id);
  }

  /**
   * Test the form with the given section.
   *
   * @param string $section_id
   *   Section ID.
   */
  protected function doFormTests($section_id) {
    $page = $this->getSession()->getPage();
    $web_assert = $this->assertSession();

    $this->createRole([], 'non_staff', 'Non staff');
    $this->createRole(['use workbench access'], 'staff', 'Staff');
    $this->createRole(['use workbench access'], 'super_staff', 'Super staff');

    $this->drupalLogin($this->setUpAdminUser());
    $this->drupalGet(sprintf('/admin/config/workflow/workbench_access/editorial_section/sections/%s/roles', $section_id));

    $editors = $page->findField('edit-editors');
    $web_assert->fieldNotExists('Non staff', $editors);
    $web_assert->fieldExists('Staff', $editors);
    $web_assert->fieldExists('Super staff', $editors);

    $page->checkField('Staff');
    $page->pressButton('Submit');
    $expected = [$section_id => 1];
    $this->assertEquals($expected, \Drupal::state()->get(RoleSectionStorageInterface::WORKBENCH_ACCESS_ROLES_STATE_PREFIX . 'editorial_section__staff', []));
  }

}
