<?php

namespace Drupal\Tests\workbench_access_protect\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\workbench_access\Traits\WorkbenchAccessTestTrait;
use Drupal\taxonomy\Entity\Term;
use Drupal\workbench_access\WorkbenchAccessManagerInterface;

/**
 * Tests protection of Taxonomy used for access control.
 *
 * @group workbench_access_protect
 */
class TaxonomyProtectTest extends BrowserTestBase {

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
   * Vocabulary used for access control.
   *
   * @var \Drupal\taxonomy\VocabularyInterface
   */
  protected $vocabulary;

  /**
   * Vocabulary not used for access control.
   *
   * @var \Drupal\taxonomy\VocabularyInterface
   */
  protected $emptyVocabulary;

  /**
   * A test term.
   *
   * @var \Drupal\taxonomy\Entity\Term
   */
  protected $term;

  /**
   * A test term that is never tagged in content.
   *
   * @var \Drupal\taxonomy\Entity\Term
   */
  protected $emptyTerm;

  /**
   * A test term that is never tagged in content.
   *
   * @var \Drupal\taxonomy\Entity\Term
   */
  protected $emptyVocabTerm;

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
    'node',
    'options',
    'system',
    'taxonomy',
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
    $this->vocabulary = $this->setUpVocabulary();
    $node_type = $this->createContentType(['type' => 'page']);
    $field = $this->setUpTaxonomyFieldForEntityType('node', $node_type->id(), $this->vocabulary->id());
    $this->scheme = $this->setUpTaxonomyScheme($node_type, $this->vocabulary);

    // Set up an non-access control vocabulary as a control.
    $this->emptyVocabulary = Vocabulary::create(['vid' => 'empty_vocabulary', 'name' => 'Empty Vocabulary']);
    $this->emptyVocabulary->save();

    // Create terms.
    $this->term = Term::create([
      'name' => 'Test Term',
      'vid' => $this->vocabulary->id(),
    ]);
    $this->term->save();

    $this->emptyTerm = Term::create([
      'name' => 'Empty Test Term',
      'vid' => $this->vocabulary->id(),
    ]);
    $this->emptyTerm->save();

    $this->emptyVocabTerm = Term::create([
      'name' => 'Empty Test Term',
      'vid' => $this->emptyVocabulary->id(),
    ]);
    $this->emptyVocabTerm->save();

    // Create users.
    $this->admin = $this->setUpAdminUser([
      'administer workbench access',
      'edit terms in workbench_access',
      'delete terms in workbench_access',
      'create terms in workbench_access',
    ]);
    $this->editor = $this->setUpUserUniqueRole([
      'administer workbench access',
      'assign workbench access',
      'edit terms in workbench_access',
      'delete terms in workbench_access',
      'create terms in workbench_access',
      'access administration pages',
      'access taxonomy overview',
      'administer taxonomy',
    ]);
  }

  /**
   * Creates content for the test.
   */
  protected function setUpTestContent() {
    $this->testNode = $this->createNode(
      [
        'title' => 'Node',
        'type' => 'page',
        'uid' => $this->admin->id(),
        WorkbenchAccessManagerInterface::FIELD_NAME => $this->term->id(),
      ]
    );
    $this->testNode->save();
  }

  /**
   * Assigns users for the test.
   */
  protected function setUpTestUser() {
    $user_storage = \Drupal::service('workbench_access.user_section_storage');
    $role_storage = \Drupal::service('workbench_access.role_section_storage');
    // Add the user to the base section.
    $user_storage->addUser($this->scheme, $this->editor, [$this->term->id()]);
    $expected = [$this->editor->id()];
    $existing_users = $user_storage->getEditors($this->scheme, $this->term->id());
    $this->assertEquals($expected, array_keys($existing_users));
  }

  /**
   * Assert a non-administrator cannot delete terms used by nodes.
   */
  public function testCannotDeleteTaxonomyWithAssignedNode() {
    $this->setUpTestContent();
    $this->assertTests();
  }

  /**
   * Assert a non-administrator cannot delete terms used by users.
   */
  public function testCannotDeleteTaxonomyWithAssignedUser() {
    $this->setUpTestUser();
    $this->assertTests();
  }

  /**
   * Runs our tests for both content and users.
   */
  private function assertTests() {
    // Login to the non-privileged account.
    $this->drupalLogin($this->editor);

    // Restricted term that has content.
    $path = '/taxonomy/term/' . $this->term->id() . '/edit';
    $this->drupalGet($path);
    $delete_path = '/taxonomy/term/' . $this->term->id() . '/delete';
    $this->assertSession()->linkByHrefNotExists($delete_path);
    $this->drupalGet($delete_path);
    $this->assertSession()->statusCodeEquals(403);

    // Unrestricted term that has no content.
    $path = '/taxonomy/term/' . $this->emptyTerm->id() . '/edit';
    $this->drupalGet($path);
    $delete_path = '/taxonomy/term/' . $this->emptyTerm->id() . '/delete';
    $this->assertSession()->linkByHrefExists($delete_path);
    $this->drupalGet($delete_path);
    $this->assertSession()->statusCodeEquals(200);

    $path = '/taxonomy/term/' . $this->emptyVocabTerm->id() . '/edit';
    $this->drupalGet($path);
    $delete_path = '/taxonomy/term/' . $this->emptyVocabTerm->id() . '/delete';
    $this->assertSession()->linkByHrefExists($delete_path);
    $delete_path = '/taxonomy/term/' . $this->emptyVocabTerm->id() . '/delete';
    $this->drupalGet($delete_path);
    $this->assertSession()->statusCodeEquals(200);

    // Test for a delete link on the vocabularies.
    $vocab_path = '/admin/structure/taxonomy/manage/' . $this->vocabulary->id() . '/delete';
    $this->drupalGet($vocab_path);
    $this->assertSession()->statusCodeEquals(403);
    $vocab_path = '/admin/structure/taxonomy/manage/' . $this->emptyVocabulary->id() . '/delete';
    $this->drupalGet($vocab_path);
    $this->assertSession()->statusCodeEquals(200);

    // Test the overview page to make sure that the delete link is handled.
    $vocab_path = '/admin/structure/taxonomy/manage/' . $this->vocabulary->id() . '/overview';
    $this->drupalGet($vocab_path);
    $delete_path = '/taxonomy/term/' . $this->term->id() . '/delete';
    $this->assertSession()->linkByHrefNotExists($delete_path);
    $delete_path = '/taxonomy/term/' . $this->emptyTerm->id() . '/delete';
    $this->assertSession()->linkByHrefExists($delete_path);

    $vocab_path = '/admin/structure/taxonomy/manage/' . $this->emptyVocabulary->id() . '/overview';
    $this->drupalGet($vocab_path);
    $delete_path = '/taxonomy/term/' . $this->emptyVocabTerm->id() . '/delete';
    $this->assertSession()->linkByHrefExists($delete_path);
  }

}
