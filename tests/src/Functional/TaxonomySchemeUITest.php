<?php

namespace Drupal\Tests\workbench_access\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\workbench_access\Entity\AccessSchemeInterface;
use Drupal\Tests\workbench_access\Traits\WorkbenchAccessTestTrait;
use Drupal\taxonomy\Entity\Term;
use Drupal\workbench_access\WorkbenchAccessManagerInterface;

/**
 * Defines a class for testing the UI to create and configure schemes.
 *
 * @group workbench_access
 */
class TaxonomySchemeUITest extends BrowserTestBase {

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
    $this->setUpTaxonomyFieldForEntityType('taxonomy_term', $vocab->id(), $this->vocabulary->id(), 'non_recursive', 'Allowed Field');
    entity_test_create_bundle('access_controlled');
    entity_test_create_bundle('notaccess_controlled');
    $this->setUpTaxonomyFieldForEntityType('entity_test', 'access_controlled', $this->vocabulary->id());
    $this->admin = $this->setUpAdminUser([
      'administer workbench access',
      'allow taxonomy term delete',
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

//  protected function tearDown() {
//    parent::tearDown();
//    // $this->term->delete();
//    $this->testNode->delete();
//  }

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
   * Tests scheme UI.
   */
  public function testSchemeUi() {
    $this->assertThatUnprivilegedUsersCannotAccessAdminPages();
    $scheme = $this->assertCreatingAnAccessSchemeAsAdmin();
    $this->assertAdminCanSelectVocabularies($scheme);
    $this->assertAdminCanAddPageNodeTypeToScheme($scheme);
    $this->assertAdminCannotAddArticleNodeTypeToScheme($scheme);
    $this->assertAdminCanAddEntityTestAccessControlledBundleToScheme($scheme);
    $this->assertAdminCannotAddEntityTestAccessAccessControlledBundleToScheme($scheme);
    $this->assertAdminCannotAddUnselectedVocabulary($scheme);
    $this->assertAdminCannotAddRecursiveTaxonomy($scheme);
    $this->assertAdminCanDeleteTaxonomy();
    $this->assertNonAdminCannotDeleteTaxonomy();
    $this->assertAnonymousGetsNoMessages();
    $this->assertUntaggedTermsMayBeDeleted();
  }

  /**
   * Assert admin can select vocabularies.
   *
   * @param \Drupal\workbench_access\Entity\AccessSchemeInterface $scheme
   *   Access scheme.
   */
  public function assertAdminCanSelectVocabularies(AccessSchemeInterface $scheme) {
    $this->drupalGet($scheme->toUrl('edit-form'));
    $this->submitForm([
      'scheme_settings[vocabularies][workbench_access]' => 1,
    ], 'Save');
    $updated = $this->loadUnchangedScheme($scheme->id());
    $this->assertEquals(['workbench_access'], $updated->getAccessScheme()->getConfiguration()['vocabularies']);
  }

  /**
   * Assert admin can add node type that has taxonomy field.
   *
   * @param \Drupal\workbench_access\Entity\AccessSchemeInterface $scheme
   *   Access scheme.
   */
  protected function assertAdminCanAddPageNodeTypeToScheme(AccessSchemeInterface $scheme) {
    $this->drupalGet($scheme->toUrl('edit-form'));
    $this->submitForm([
      'scheme_settings[fields][node:page:field_workbench_access]' => 1,
    ], 'Save');
    $updated = $this->loadUnchangedScheme($scheme->id());
    $this->assertTrue($updated->getAccessScheme()->applies('node', 'page'));
  }

  /**
   * Assert admin cannot add node type that has no taxonomy field.
   *
   * @param \Drupal\workbench_access\Entity\AccessSchemeInterface $scheme
   *   Access scheme.
   */
  protected function assertAdminCannotAddArticleNodeTypeToScheme(AccessSchemeInterface $scheme) {
    $this->drupalGet($scheme->toUrl('edit-form'));
    $this->assertSession()->fieldNotExists('scheme_settings[fields][node:article:field_workbench_access]');
    $this->assertFalse($scheme->getAccessScheme()->applies('node', 'article'));
  }

  /**
   * Assert admin can add entity test bundle that has taxonomy field.
   *
   * @param \Drupal\workbench_access\Entity\AccessSchemeInterface $scheme
   *   Access scheme.
   */
  protected function assertAdminCanAddEntityTestAccessControlledBundleToScheme(AccessSchemeInterface $scheme) {
    $this->drupalGet($scheme->toUrl('edit-form'));
    $this->submitForm([
      'scheme_settings[fields][entity_test:access_controlled:field_workbench_access]' => 1,
    ], 'Save');
    $updated = $this->loadUnchangedScheme($scheme->id());
    $this->assertTrue($updated->getAccessScheme()->applies('entity_test', 'access_controlled'));
  }

  /**
   * Assert admin cannot add entity test bundle that has no taxonomy field.
   *
   * @param \Drupal\workbench_access\Entity\AccessSchemeInterface $scheme
   *   Access scheme.
   */
  protected function assertAdminCannotAddEntityTestAccessAccessControlledBundleToScheme(AccessSchemeInterface $scheme) {
    $this->drupalGet($scheme->toUrl('edit-form'));
    $this->assertSession()->fieldNotExists('scheme_settings[fields][entity_test:not_access_controlled:field_workbench_access]');
    $this->assertFalse($scheme->getAccessScheme()->applies('entity_test', 'not_access_controlled'));
  }

  /**
   * Assert admin cannot add a field that is not in the assigned vocabularies.
   *
   * @param \Drupal\workbench_access\Entity\AccessSchemeInterface $scheme
   *   Access scheme.
   */
  protected function assertAdminCannotAddUnselectedVocabulary(AccessSchemeInterface $scheme) {
    $this->drupalGet($scheme->toUrl('edit-form'));
    $this->submitForm([
      'scheme_settings[vocabularies][workbench_access]' => 0,
      'scheme_settings[vocabularies][selected]' => 1,
      'scheme_settings[fields][entity_test:access_controlled:field_workbench_access]' => 1,
      'scheme_settings[fields][node:page:field_workbench_access]' => 0,
    ], 'Save');
    $this->assertSession()->pageTextContains('The field Section on entity_test entities of type access_controlled is not in the selected vocabularies.');
    $this->assertSession()->pageTextNotContains('The field Section on node entities of type page is not in the selected vocabularies.');
  }


  /**
   * Assert admin cannot add a field that references its own vocabulary.
   *
   * `@param \Drupal\workbench_access\Entity\AccessSchemeInterface $scheme
   *   Access scheme.
   */
  protected function assertAdminCannotAddRecursiveTaxonomy(AccessSchemeInterface $scheme) {
    $this->drupalGet($scheme->toUrl('edit-form'));
    $this->assertSession()->pageTextContains('Allowed Field');
    $this->assertSession()->pageTextNotContains('Recursive Field');
    $this->assertSession()->pageTextNotContains('Term Parents');
  }

  /**
   * Assert that an administrator can delete a term that is actively used
   * to tag content.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Behat\Mink\Exception\ResponseTextException
   */
  protected function assertAdminCanDeleteTaxonomy() {

    $path = '/taxonomy/term/' . $this->term->id()  . '/edit';
    $this->drupalGet($path);
    $this->assertSession()->pageTextContains('The term Test Term is being used to tag content');
    $this->assertSession()->pageTextNotContains("The term Test Term is being used to tag content and may not be deleted.");
    $this->assertSession()->linkExists("Delete");

  }

  /**
   * Asset a non-administrator cannot delete terms that are actively used
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Behat\Mink\Exception\ResponseTextException
   */
  protected function assertNonAdminCannotDeleteTaxonomy() {

    // Switch user to the non-privileged account.
    $this->drupalLogout();
    $this->drupalLogin($this->admin2);

    $path = '/taxonomy/term/' . $this->term->id()  . '/edit';
    $this->drupalGet($path);
    $this->assertSession()->pageTextContains('The term Test Term is being used to tag content');
    $this->assertSession()->pageTextContains("The term Test Term is being used to tag content and may not be deleted.");
    $this->assertSession()->linkNotExists("Delete");

    $delete_path = '/taxonomy/term/' . $this->term->id() . '/delete';

    $this->drupalGet($delete_path);
    $this->assertSession()->statusCodeEquals(403);
    $this->assertSession()->pageTextContains("The term Test Term is being used to tag content and may not be deleted.");

    // Test the overview page to make sure that a delete is not present.
    $vocab_path = '/admin/structure/taxonomy/manage/' . $this->vocabulary->id() . '/overview';
    $this->drupalGet($vocab_path);
    $this->assertSession()->linkByHrefNotExists($delete_path);

    // Switch user back to the privileged account.
    $this->drupalLogout();
    $this->drupalLogin($this->admin);

  }

  /**
   * Assert that anonymous visitors are not told anything additional about
   * this term, besides the existence of the URL as provided by Drupal.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Behat\Mink\Exception\ResponseTextException
   */
  protected function assertAnonymousGetsNoMessages() {

    $this->drupalLogout();
    $delete_path = '/taxonomy/term/' . $this->term->id() . '/delete';

    $this->drupalGet($delete_path);
    $this->assertSession()->statusCodeEquals(403);
    $this->assertSession()->pageTextNotContains("The term Test Term is being used to tag content and may not be deleted.");
    $this->drupalLogin($this->admin);

  }

  protected function assertUntaggedTermsMayBeDeleted()  {
    // Switch user to the non-privileged account.
    $this->drupalLogout();
    $this->drupalLogin($this->admin2);

    $path = '/taxonomy/term/' . $this->emptyTerm->id()  . '/edit';
    $this->drupalGet($path);
    $this->assertSession()->pageTextNotContains('The term Empty Test Term is being used to tag content');
    $this->assertSession()->pageTextNotContains("The term Empty Test Term is being used to tag content and may not be deleted.");
    $this->assertSession()->linkExists("Delete");

    $delete_path = '/taxonomy/term/' . $this->emptyTerm->id() . '/delete';

    $this->drupalGet($delete_path);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextNotContains("The term Empty Test Term is being used to tag content and may not be deleted.");

    // Test the overview page to make sure that a delete is present.
    $vocab_path = '/admin/structure/taxonomy/manage/' . $this->vocabulary->id() . '/overview';
    $this->drupalGet($vocab_path);
    $this->assertSession()->linkByHrefExists($delete_path);

    // Switch user back to the privileged account.
    $this->drupalLogout();
    $this->drupalLogin($this->admin);
  }



}
