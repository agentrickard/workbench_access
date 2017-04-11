<?php

namespace Drupal\Tests\workbench_access\Functional;

use Drupal\taxonomy\Entity\Term;
use Drupal\Tests\BrowserTestBase;
use Drupal\user\Entity\User;
use Drupal\workbench_access\WorkbenchAccessManagerInterface;

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
  ];

  /**
   * Tests that the correct users are displayed on the access by user form.
   */
  public function testAccessByUserForm() {
    $web_assert = $this->assertSession();
    $page = $this->getSession()->getPage();

    $node_type = $this->setUpContentType();
    $vocab = $this->setUpVocabulary();
    $this->setUpTaxonomyField($node_type, $vocab);
    $this->setUpTaxonomyScheme($node_type, $vocab);

    // Set up some roles and terms for this test.
    $staff_term = Term::create([
      'vid' => $vocab->id(),
      'name' => 'Staff',
    ]);
    $staff_term->save();

    $non_staff_rid = $this->createRole([], 'non_staff');
    $staff_rid = $this->createRole(['use workbench access'], 'staff');
    $super_staff_rid = $this->createRole(['use workbench access'], 'super_staff');

    $user1 = $this->createUserWithRole($non_staff_rid);
    $user2 = $this->createUserWithRole($staff_rid);
    $user3 = $this->createUserWithRole($super_staff_rid);
    $this->drupalLogin($this->setUpAdminUser());
    $this->drupalGet(sprintf('/admin/config/workflow/workbench_access/sections/%s/users', $staff_term->id()));

    $editors = $page->findField('edit-editors');
    $web_assert->fieldNotExists($user1->label(), $editors);
    $web_assert->fieldExists($user2->label(), $editors);
    $web_assert->fieldExists($user3->label(), $editors);

    $page->checkField($user2->label());
    $page->pressButton('Submit');

    $user = User::load($user2->id());
    $expected = [['value' => $staff_term->id()]];
    $this->assertEquals($expected, $user->get(WorkbenchAccessManagerInterface::FIELD_NAME)->getValue());
  }

}
