<?php

namespace Drupal\Tests\workbench_access\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;
use Drupal\workbench_access\Entity\AccessSchemeInterface;

/**
 * Defines a class for testing the UI to create and configure schemes.
 *
 * @group workbench_access
 */
class TaxonomySchemeUiTest extends BrowserTestBase {

  use WorkbenchAccessTestTrait;

  /**
   * Admin user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $admin;

  /**
   * Vocabulary.
   *
   * @var \Drupal\taxonomy\VocabularyInterface
   */
  protected $vocabulary;

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
    entity_test_create_bundle('access_controlled');
    entity_test_create_bundle('notaccess_controlled');
    $this->setUpTaxonomyFieldForEntityType('entity_test', 'access_controlled', $this->vocabulary->id());
    $this->admin = $this->setUpAdminUser(['administer workbench access']);
    $this->placeBlock('local_actions_block');
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

  }

  /**
   * Assert that unprivileged users cannot access admin pages.
   */
  protected function assertThatUnprivilegedUsersCannotAccessAdminPages() {
    $this->drupalGet(Url::fromRoute('entity.access_scheme.collection'));
    $assert = $this->assertSession();
    $assert->statusCodeEquals(403);
  }

  /**
   * Assert that admin can create an access scheme.
   *
   * @return \Drupal\workbench_access\Entity\AccessSchemeInterface
   *   Created scheme.
   */
  protected function assertCreatingAnAccessSchemeAsAdmin() {
    $this->drupalLogin($this->admin);
    $this->drupalGet(Url::fromRoute('entity.access_scheme.collection'));
    $assert = $this->assertSession();
    $assert->statusCodeEquals(200);
    $this->clickLink('Add Access scheme');
    $this->submitForm([
      'label' => 'Section',
      'plural_label' => 'Sections',
      'id' => 'editorial_section',
      'scheme' => 'taxonomy',
    ], 'Save');
    /** @var \Drupal\workbench_access\Entity\AccessSchemeInterface $scheme */
    $scheme = $this->loadUnchangedScheme('editorial_section');
    $this->assertEquals('Section', $scheme->label());
    $this->assertEquals('Sections', $scheme->getPluralLabel());
    $this->assertEquals($scheme->toUrl('edit-form')->setAbsolute()->toString(), $this->getSession()->getCurrentUrl());
    return $scheme;
  }

  /**
   * Assert admin can select vocabularies.
   *
   * @param \Drupal\workbench_access\Entity\AccessSchemeInterface $scheme
   *   Access scheme.
   */
  function assertAdminCanSelectVocabularies(AccessSchemeInterface $scheme) {
    $this->drupalGet($scheme->toUrl('edit-form'));
    $this->submitForm([
      'scheme_settings[vocabularies]' => ['workbench_access'],
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
      'new_field' => 'node:page:field_workbench_access',
    ], 'Use for access control');
    $this->submitForm([], 'Save');
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
    $this->assertSession()->optionNotExists('new_field', 'node:article:field_workbench_access');
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
      'new_field' => 'entity_test:access_controlled:field_workbench_access',
    ], 'Use for access control');
    $this->submitForm([], 'Save');
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
    $this->assertSession()->optionNotExists('new_field', 'entity_test:not_access_controlled:field_workbench_access');
    $this->assertFalse($scheme->getAccessScheme()->applies('entity_test', 'not_access_controlled'));
  }

  /**
   * Loads the given scheme
   * @param string $scheme_id
   *   Scheme ID.
   *
   * @return \Drupal\workbench_access\Entity\AccessSchemeInterface
   *   Unchanged scheme.
   */
  protected function loadUnchangedScheme($scheme_id) {
    return $this->container->get('entity_type.manager')
      ->getStorage('access_scheme')
      ->loadUnchanged($scheme_id);
  }

}
