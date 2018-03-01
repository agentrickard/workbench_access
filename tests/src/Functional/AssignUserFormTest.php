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
    $terms = ['workbench_access' => 'Access'];
    $staff_term = Term::create([
      'vid' => $vocab->id(),
      'name' => 'Staff',
    ]);
    $staff_term->save();
    $terms[$staff_term->id()] = 'Staff';
    $super_staff_term = Term::create([
      'vid' => $vocab->id(),
      'name' => 'Super staff',
    ]);
    $super_staff_term->save();
    $terms[$super_staff_term->id()] = 'Super staff';
    $base_term = Term::create([
      'vid' => $vocab->id(),
      'name' => 'Editor',
    ]);
    $base_term->save();
    $terms[$base_term->id()] = 'Editor';

    // Set up test menu scheme.
    $node_type = $this->createContentType(['type' => 'article']);
    $menu_scheme = $this->setUpMenuScheme([$node_type->id()], ['main'], 'menu_section');

    // Set up some menu links for this test.
    $links = ['main' => 'Main navigation'];
    $staff_link = MenuLinkContent::create([
      'title' => 'Link 1',
      'link' => [['uri' => 'route:<front>']],
      'menu_name' => 'main',
    ]);
    $staff_link->save();
    $links[$staff_link->getPluginId()] = 'Link 1';
    $super_staff_link = MenuLinkContent::create([
      'title' => 'Link 2',
      'link' => [['uri' => 'route:<front>']],
      'menu_name' => 'main',
    ]);
    $super_staff_link->save();
    $links[$super_staff_link->getPluginId()] = 'Link 2';
    $base_link = MenuLinkContent::create([
      'title' => 'Link 3',
      'link' => [['uri' => 'route:<front>']],
      'menu_name' => 'main',
    ]);
    $base_link->save();
    $links[$base_link->getPluginId()] = 'Link 3';

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
    foreach ($terms as $id => $term) {
      $assert->pageTextContains($term);
      $assert->fieldExists('active_taxonomy_section['. $id .']');
    }
    foreach ($links as $id => $link) {
      $assert->pageTextContains($link);
      $assert->fieldExists('active_menu_section['. $id .']');
    }

    // Add the user to two taxonomy sections.
    $page = $this->getSession()->getPage();
    $page->checkField('active_taxonomy_section[2]');
    $page->checkField('active_taxonomy_section[3]');
    $page->pressButton('save');

    // Check the results.
    $assert = $this->assertSession();
    $assert->statusCodeEquals(200);
    $assert->checkboxChecked('active_taxonomy_section[2]');
    $assert->checkboxChecked('active_taxonomy_section[3]');
  }

}
