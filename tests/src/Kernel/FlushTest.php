<?php

namespace Drupal\Tests\workbench_access\Kernel;


use Drupal\KernelTests\KernelTestBase;
use Drupal\taxonomy\Entity\Term;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\Tests\workbench_access\Functional\WorkbenchAccessTestTrait;

class FlushTest extends KernelTestBase {

  use WorkbenchAccessTestTrait;
  use NodeCreationTrait;
  use ContentTypeCreationTrait;
  use UserCreationTrait;

  /**
   * Access vocabulary.
   *
   * @var \Drupal\taxonomy\VocabularyInterface
   */
  protected $vocabulary;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'text',
    'system',
    'user',
    'workbench_access',
    'field',
    'filter',
    'taxonomy',
    'options',
  ];

  /**
   * Access scheme.
   *
   * @var \Drupal\workbench_access\Entity\AccessSchemeInterface
   */
  protected $scheme;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('node');
    $this->installConfig(['filter', 'node', 'workbench_access']);
    $this->installEntitySchema('user');
    $this->installEntitySchema('taxonomy_term');
    $this->installSchema('system', ['key_value', 'sequences']);
    module_load_install('workbench_access');
    workbench_access_install();
    $node_type = $this->createContentType(['type' => 'page']);
    $this->createContentType(['type' => 'article']);
    $this->vocabulary = $this->setUpVocabulary();
    $this->setUpTaxonomyFieldForEntityType('node', $node_type->id(), $this->vocabulary->id());
    $this->scheme = $this->setUpTaxonomyScheme($node_type, $this->vocabulary);
  }

  /**
   * Tests sections are flushed when a scheme is deleted.
   */
  public function testSectionFlush() {
    $userStorage = $this->container->get('workbench_access.user_section_storage');
    $roleStorage = $this->container->get('workbench_access.role_section_storage');
    $role = $this->createRole([
      'access content',
      'create page content',
      'edit any page content',
      'delete any page content',
    ]);
    $section = Term::create([
      'vid' => $this->vocabulary->id(),
      'name' => 'Some section',
    ]);
    $user = $this->createUser();
    $user->addRole($role);
    $user->save();
    $roleStorage->addRole($this->scheme, $role, [$section->id()]);
    $this->assertEquals([$section->id()], $roleStorage->getRoleSections($this->scheme, $user));
    $userStorage->addUser($this->scheme, $user->id(), [$section->id()]);
    $this->assertEquals([$section->id()], $userStorage->getUserSections($this->scheme, $user->id(), FALSE));
    $this->scheme->delete();
    $this->assertEmpty($userStorage->getUserSections($this->scheme, $user->id(), FALSE));
    $this->assertEmpty($roleStorage->getRoleSections($this->scheme, $user));
  }

}
