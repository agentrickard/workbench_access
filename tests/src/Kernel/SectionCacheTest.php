<?php

namespace Drupal\Tests\workbench_access\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\taxonomy\Entity\Term;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\Tests\workbench_access\Traits\WorkbenchAccessTestTrait;
use Drupal\workbench_access\Entity\AccessScheme;
use Drupal\workbench_access\WorkbenchAccessManagerInterface;

/**
 * Tests the internal caching of section data.
 *
 * @group workbench_access
 */
class SectionCacheTest extends KernelTestBase {

  use WorkbenchAccessTestTrait;
  use UserCreationTrait;

  /**
   * Access vocabulary.
   *
   * @var \Drupal\taxonomy\VocabularyInterface
   */
  protected $vocabulary;

  /**
   * User storage handler.
   *
   * @var \Drupal\workbench_access\UserSectionStorageInterface
   */
  protected $userSectionStorage;

  /**
   * Access scheme.
   *
   * @var \Drupal\workbench_access\Entity\AccessSchemeInterface
   */
  protected $scheme;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'workbench_access',
    'field',
    'taxonomy',
    'options',
    'text',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(['workbench_access']);
    $this->installEntitySchema('user');
    $this->installEntitySchema('taxonomy_term');
    $this->installSchema('system', ['key_value', 'sequences']);
    module_load_install('workbench_access');
    workbench_access_install();
    $this->vocabulary = $this->setUpVocabulary();
    // The user section storage service.
    $this->userSectionStorage = \Drupal::getContainer()->get('workbench_access.user_section_storage');
    $this->scheme = AccessScheme::create([
      'id' => 'editorial_section',
      'label' => 'Editorial section',
      'plural_label' => 'Editorial sections',
      'scheme' => 'taxonomy',
      'scheme_settings' => [
        'vocabularies' => [$this->vocabulary->id()],
        'fields' => [],
      ],
    ]);
    $this->scheme->save();
  }

  /**
   * Test create access integration.
   */
  public function testSectionCache() {
    // The first user in a kernel test gets UID 1, so we need to make sure we're
    // not testing with that user.
    $this->createUser();
    // Create a section.
    $term = Term::create([
      'vid' => $this->vocabulary->id(),
      'name' => 'Some section',
    ]);
    $term->save();
    // Create one user and assign to the section.
    $permissions = [
      'use workbench access',
    ];
    $editor = $this->createUser($permissions);
    $editor->{WorkbenchAccessManagerInterface::FIELD_NAME} = $this->scheme->id() . ':' . $term->id();
    $editor->save();

    // Now fetch the sections for this user. Count should be 1.
    $sections = $this->userSectionStorage->getUserSections($this->scheme, $editor->id());
    $this->assertTrue(count($sections) == 1);

    // Create a new section.
    $term2 = Term::create([
      'vid' => $this->vocabulary->id(),
      'name' => 'Some new section',
    ]);
    $term2->save();

    // Add to the user.
    $this->userSectionStorage->addUser($this->scheme, $editor->id(), [$term2->id()]);

    // Now fetch the sections for this user. Count should be 2.
    $sections = $this->userSectionStorage->getUserSections($this->scheme, $editor->id());
    $this->assertTrue(count($sections) == 2);

    // Now remove and test again.
    $this->userSectionStorage->removeUser($this->scheme, $editor->id(), [$term2->id()]);
    $sections = $this->userSectionStorage->getUserSections($this->scheme, $editor->id());
    $this->assertTrue(count($sections) == 1);

    $this->assertEquals([$editor->id() => $editor->label()], $this->userSectionStorage->getEditors($this->scheme, $term->id()));
  }

}
