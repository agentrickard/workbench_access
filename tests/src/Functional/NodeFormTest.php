<?php

namespace Drupal\Tests\workbench_access\Functional;

use Drupal\taxonomy\Entity\Term;
use Drupal\Tests\BrowserTestBase;
use Drupal\workbench_access\WorkbenchAccessManagerInterface;

/**
 * Tests for the node form.
 *
 * @group workbench_access
 */
class NodeFormTest extends BrowserTestBase {

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
  public function testNodeForm() {
    // Set up a content type, taxonomy field, and taxonomy scheme.
    $node_type = $this->setUpContentType();
    $vocab = $this->setUpVocabulary();
    $this->setUpTaxonomyField($node_type, $vocab);
    $this->setUpTaxonomyScheme($node_type, $vocab);
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
    $editor->{WORKBENCH_ACCESS_FIELD} = $base_term->id();
    $editor->save();

    $staff_rid = $this->createRole([], 'staff');
    $super_staff_rid = $this->createRole([], 'super_staff');
    // Set the role -> term mapping.
    \Drupal::state()->set('workbench_access_roles_' . $staff_rid, [$staff_term->id() => 1]);
    \Drupal::state()->set('workbench_access_roles_' . $super_staff_rid, [$super_staff_term->id() => 1]);

    $web_assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    // Assert we can't see the options yet.
    $web_assert->optionNotExists(WorkbenchAccessManagerInterface::FIELD_NAME, $staff_term->getName());
    $web_assert->optionNotExists(WorkbenchAccessManagerInterface::FIELD_NAME, $super_staff_term->getName());

    // Add the staff role and check the option exists.
    $editor->addRole($staff_rid);
    $editor->save();
    $this->drupalGet('node/add/page');
    $web_assert->optionExists(WorkbenchAccessManagerInterface::FIELD_NAME, $staff_term->getName());

    // Add the super staff role and check both options exist.
    $editor->addRole($super_staff_rid);
    $editor->save();
    $this->drupalGet('node/add/page');
    $web_assert->optionExists(WorkbenchAccessManagerInterface::FIELD_NAME, $staff_term->getName());
    $web_assert->optionExists(WorkbenchAccessManagerInterface::FIELD_NAME, $super_staff_term->getName());
  }

}
