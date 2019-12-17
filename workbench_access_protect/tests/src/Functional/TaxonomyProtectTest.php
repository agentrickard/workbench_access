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
  protected $admin2;

  /**
   * Vocabulary.
   *
   * @var \Drupal\taxonomy\VocabularyInterface
   */
  protected $vocabulary;

  /**
   * A Test term
   *
   * @var \Drupal\taxonomy\Entity\Term;
   */
  protected $term;

  /**
   * A Test term that is never tagged in content.
   *
   * @var \Drupal\taxonomy\Entity\Term;
   */
  protected $emptyTerm;


  /**
   * A Test Node
   *
   * @var \Drupal\node\Entity\Node;
   */
  protected $testNode;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'workbench_access',
    'workbench_access_protect',
    'node',
    'taxonomy',
    'options',
    'user',
    'block',
    'entity_test',
    'system',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->createContentType(['type' => 'page']);
    $this->createContentType(['type' => 'article']);
    $this->vocabulary = $this->setUpVocabulary();
    $this->setUpTaxonomyFieldForEntityType('node', 'page', $this->vocabulary->id());
    $this->setUpTaxonomyFieldForEntityType('taxonomy_term', $this->vocabulary->id(), $this->vocabulary->id(), 'recursive', 'Recursive Field');
    $vocab = Vocabulary::create(['vid' => 'selected', 'name' => 'Selected Vocabulary']);
    $vocab->save();
    $this->setUpTaxonomyFieldForEntityType('taxonomy_term', $this->vocabulary->id(), $this->vocabulary->id(), 'non_recursive', 'Allowed Field');
    entity_test_create_bundle('access_controlled');
    entity_test_create_bundle('notaccess_controlled');
    $this->setUpTaxonomyFieldForEntityType('entity_test', 'access_controlled', $this->vocabulary->id());
    $this->admin = $this->setUpAdminUser([
      'administer workbench access',
      'edit terms in workbench_access',
      'delete terms in workbench_access',
      'create terms in workbench_access',
    ]);
    $this->admin2 = $this->setUpUserUniqueRole([
      'administer workbench access',
      'assign workbench access',
      'edit terms in workbench_access',
      'delete terms in workbench_access',
      'create terms in workbench_access',
      'access administration pages',
      'access taxonomy overview',
      'administer taxonomy',
    ]);
    $this->placeBlock('local_actions_block');

    $this->setUpTestContent();



  }

  protected function setUpTestContent() {
    // create a test term
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


    // create some test nodes
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
   * Asset a non-administrator cannot delete terms that are actively used
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
    public function testAssertCannotDeleteTaxonomy() {

      // Switch user to the non-privileged account.
      $this->drupalLogin($this->admin2);

      $path = '/taxonomy/term/' . $this->term->id()  . '/edit';
      $this->drupalGet($path);
      $this->assertSession()->linkNotExistsExact("Delete");

      $delete_path = '/taxonomy/term/' . $this->term->id() . '/delete';
      $this->drupalGet($delete_path);
      $this->assertSession()->statusCodeEquals(403);

      // Test the overview page to make sure that a delete is not present.
      $vocab_path = '/admin/structure/taxonomy/manage/' . $this->vocabulary->id() . '/overview';
      $this->drupalGet($vocab_path);
      $delete_path = '/admin/structure/taxonomy/manage/' . $this->vocabulary->id() . '/delete';
      $this->assertSession()->linkByHrefNotExists($delete_path);

      // Switch user back to the privileged account.
      $this->drupalLogout();
    }

}
