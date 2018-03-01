<?php

namespace Drupal\Tests\workbench_access\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;
use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\taxonomy\Entity\Term;
use Drupal\workbench_access\Entity\AccessSchemeInterface;
use Drupal\Tests\workbench_access\Traits\WorkbenchAccessTestTrait;

/**
 * Tests for the user account form.
 *
 * @group workbench_access
 */
class AssignUserFormTest extends BrowserTestBase {

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
   * Tests that the AssignUserForm works correctly.
   */
  public function testAssignUserForm() {
    // Set up test taxonomy scheme.
    $node_type = $this->createContentType(['type' => 'page']);
    $vocab = $this->setUpVocabulary();
    $this->setUpTaxonomyFieldForEntityType('node', $node_type->id(), $vocab->id());
    $taxonomy_scheme = $this->setUpTaxonomyScheme($node_type, $vocab, 'taxonomy_section');

    // Create terms for the test.
    $staff_term = Term::create([
      'vid' => $vocab->id(),
      'name' => 'Staff',
    ]);
    $staff_term->save();
    $super_staff_term = Term::create([
      'vid' => $vocab->id(),
      'name' => 'Super staff',
    ]);
    $super_staff_term->save();
    $base_term = Term::create([
      'vid' => $vocab->id(),
      'name' => 'Editor',
    ]);
    $base_term->save();

    // Set up test menu scheme.
    $node_type = $this->createContentType(['type' => 'article']);
    $menu_scheme = $this->setUpMenuScheme([$node_type->id()], ['main'], 'menu_section');

    // Set up some menu links for this test.
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

    // Set some users with permissions.
    // Super admin.
    $admin_rid = $this->createRole([
      'access administration pages',
      'assign workbench access',
      'bypass workbench access',
      'create page content',
      'edit any page content',
      'delete any page content',
      'create article content',
      'edit any article content',
      'delete any article content',
      'administer menu',
      'use workbench access',
      'access user profiles',
    ], 'admin');
    $admin_user = $this->createUserWithRole($admin_rid);

    // Partial admin.
    $partial_rid = $this->createRole([
      'access administration pages',
      'assign selected workbench access',
      'create page content',
      'edit any page content',
      'delete any page content',
      'create article content',
      'edit any article content',
      'delete any article content',
      'administer menu',
      'use workbench access',
      'access user profiles',
    ], 'partial');
    $partial_user = $this->createUserWithRole($partial_rid);

    // No admin.
    $none_rid = $this->createRole([
      'access administration pages',
      'create page content',
      'edit any page content',
      'delete any page content',
      'create article content',
      'edit any article content',
      'delete any article content',
      'use workbench access',
      'access user profiles',
    ], 'none');
    $none_user = $this->createUserWithRole($none_rid);

    // Check page access.
    $this->drupalLogin($admin_user);
    $this->drupalGet(Url::fromRoute('entity.section_association.edit', ['user' => $none_user->id()]));
    $assert = $this->assertSession();
    $assert->statusCodeEquals(200);

    // Check page options.
    $assert->pageTextContains('Taxonomy sections');
    $assert->pageTextContains('Menu sections');
  }

}
