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
   * Assert admin can select vocabularies.
   *
   * @param \Drupal\workbench_access\Entity\AccessSchemeInterface $scheme
   *   Access scheme.
   */
  function assertAdminCanSelectVocabularies(AccessSchemeInterface $scheme) {
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

}
