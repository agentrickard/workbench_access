<?php

namespace Drupal\Tests\workbench_access\Functional;

use Drupal\taxonomy\Entity\Term;
use Drupal\Tests\BrowserTestBase;
use Drupal\workbench_access\WorkbenchAccessManagerInterface;
use Drupal\Tests\workbench_access\Traits\WorkbenchAccessTestTrait;

/**
 * Tests for the node form.
 *
 * @group workbench_access
 */
class NodeFormMultipleTest extends BrowserTestBase {

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
   * Tests that the user can see all valid options on the node form.
   */
  public function testNodeMultipleForm() {
    // Set up a content type, taxonomy field, and taxonomy scheme.
    $node_type = $this->createContentType(['type' => 'page']);
    $vocab = $this->setUpVocabulary();
    $field_name = WorkbenchAccessManagerInterface::FIELD_NAME;
    $field = $this->setUpTaxonomyFieldForEntityType('node', $node_type->id(), $vocab->id(), $field_name, 'Section', 3);
    $scheme = $this->setUpTaxonomyScheme($node_type, $vocab);
    $user_storage = \Drupal::service('workbench_access.user_section_storage');
    $role_storage = \Drupal::service('workbench_access.role_section_storage');

    // Set up an editor and log in as them.
    $editor = $this->setUpEditorUser();
    $this->drupalLogin($editor);

    // Set up some roles and terms for this test.
    // Create terms and roles.
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

    // Add the user to the base section and the staff section.
    $user_storage->addUser($scheme, $editor, [$base_term->id(), $staff_term->id()]);
    $expected = [$editor->id()];
    $existing_users = $user_storage->getEditors($scheme, $base_term->id());
    $this->assertEquals($expected, array_keys($existing_users));
    $existing_users = $user_storage->getEditors($scheme, $staff_term->id());
    $this->assertEquals($expected, array_keys($existing_users));

    $web_assert = $this->assertSession();

    // Create a page as super-admin.
    $admin = $this->setUpAdminUser([
      'bypass node access',
      'bypass workbench access']);
    $this->drupalLogin($admin);

    $this->drupalGet('node/add/page');
    $web_assert->optionExists($field_name . '[]', $base_term->getName());
    $web_assert->optionExists($field_name . '[]', $staff_term->getName());
    $web_assert->optionExists($field_name . '[]', $super_staff_term->getName());

    // Save the node.
    $edit['title[0][value]'] = 'Test node';
    $edit[$field_name . '[]'] = [
      $base_term->id(),
      $staff_term->id(),
      $super_staff_term->id(),
    ];
    $this->drupalPostForm('node/add/page', $edit, 'Save');

    // Get node data. Note that we create one new node for each test case.
    $nid = 1;
    $storage = \Drupal::entityTypeManager()->getStorage('node');
    $node = $storage->load($nid);

    // Check that three values are set.
    $values = $scheme->getAccessScheme()->getEntityValues($node);
    $this->assert(count($values) == 3, 'Node saved with three sections.');

    // TODO - login and save as the editor. Check that values are retained.
    $this->drupalLogin($editor);
    $this->drupalGet('node/1/edit');
    $web_assert->optionExists($field_name . '[]', $base_term->getName());
    $web_assert->optionExists($field_name . '[]', $staff_term->getName());
    $web_assert->optionNotExists($field_name . '[]', $super_staff_term->getName());

    /* TODO FIX THIS *
    $edit['title[0][value]'] = 'Updated node';
    $edit[$field_name . '[]'] = [
      $base_term->id() => 1,
      $staff_term->id() => 0,
    ];
    $this->drupalPostForm('node/1/edit', $edit, 'Save');

    // Check that three values are still set.
    $node = $storage->load($nid);
    $values = $scheme->getAccessScheme()->getEntityValues($node);
    $this->assert(count($values) == 2, 'Node saved with two sections. ' . count($values) );
    */
  }

}
