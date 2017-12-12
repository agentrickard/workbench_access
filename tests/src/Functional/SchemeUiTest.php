<?php

namespace Drupal\Tests\workbench_access\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Defines a class for testing the UI to create and configure schemes.
 *
 * @group workbench_access
 */
class SchemeUiTest extends BrowserTestBase {

  use WorkbenchAccessTestTrait;

  /**
   * Admin user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $admin;

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
    'system',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $node_type = $this->createContentType(['type' => 'page']);
    $vocabulary = $this->setUpVocabulary();
    $this->setUpTaxonomyField($node_type, $vocabulary);
    $this->admin = $this->setUpAdminUser(['administer workbench access']);
    $this->placeBlock('local_actions_block');
  }

  /**
   * Tests scheme UI.
   */
  public function testSchemeUi() {
    $this->assertThatUnprivilegedUsersCannotAccessAdminPages();
    $scheme = $this->assertCreatingAnAccessSchemeAsAdmin();
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
    $scheme = $this->container->get('entity_type.manager')->getStorage('access_scheme')->load('editorial_section');
    $this->assertEquals('Section', $scheme->label());
    $this->assertEquals('Sections', $scheme->getPluralLabel());
    $this->assertEquals($scheme->toUrl('edit-form')->setAbsolute()->toString(), $this->getSession()->getCurrentUrl());
    return $scheme;
  }

}
