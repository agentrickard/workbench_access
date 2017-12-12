<?php

namespace Drupal\Tests\workbench_access\Functional;

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
    'system',
  ];

  /**
   * Tests that the correct users are displayed on the access by user form.
   */
  public function testAccessByRoleForm() {
    $web_assert = $this->assertSession();
    $page = $this->getSession()->getPage();

    // Set up a content type, taxonomy field, and taxonomy scheme.
    $node_type = $this->createContentType(['type' => 'page']);
    $vocab = $this->setUpVocabulary();
    $this->setUpTaxonomyFieldForEntityType('node', $node_type->id(), $vocab->id());
    $this->setUpTaxonomyScheme($node_type, $vocab);

    // Set up some roles and terms for this test.
    // Create terms and roles.
    $staff_term = Term::create([
      'vid' => $vocab->id(),
      'name' => 'Staff',
    ]);
    $staff_term->save();

    $this->createRole([], 'non_staff', 'Non staff');
    $this->createRole(['use workbench access'], 'staff', 'Staff');
    $this->createRole(['use workbench access'], 'super_staff', 'Super staff');

    $this->drupalLogin($this->setUpAdminUser());
    $this->drupalGet(sprintf('/admin/config/workflow/workbench_access/sections/%s/roles', $staff_term->id()));

    $editors = $page->findField('edit-editors');
    $web_assert->fieldNotExists('Non staff', $editors);
    $web_assert->fieldExists('Staff', $editors);
    $web_assert->fieldExists('Super staff', $editors);

    $page->checkField('Staff');
    $page->pressButton('Submit');
    $expected = [$staff_term->id() => 1];
    $this->assertEquals($expected, \Drupal::state()->get(RoleSectionStorageInterface::WORKBENCH_ACCESS_ROLES_STATE_PREFIX . 'staff', []));
  }

}
