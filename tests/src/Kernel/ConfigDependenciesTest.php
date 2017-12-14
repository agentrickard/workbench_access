<?php

namespace Drupal\Tests\workbench_access\Unit;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\workbench_access\Functional\WorkbenchAccessTestTrait;
use Drupal\workbench_access\Plugin\views\field\Section;

/**
 * Defines a class for testing config dependencies.
 */
class ConfigDependenciesTest extends KernelTestBase {

  use WorkbenchAccessTestTrait;
  use ContentTypeCreationTrait;

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
   * Access scheme.
   *
   * @var \Drupal\workbench_access\Entity\AccessSchemeInterface
   */
  protected $menuScheme;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('node');
    $this->installConfig(['filter', 'node', 'workbench_access', 'system']);
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
    $this->menuScheme = $this->setUpMenuScheme($node_type, ['main'], 'menu_scheme');
  }

  /**
   * Tests views field dependencies.
   */
  public function testViewsFieldDependencies() {
    $definition = [
      'scheme' => 'editorial_section',
    ];
    $handler = Section::create($this->container, [], 'section:editorial_section', $definition);

    $dependencies = $handler->calculateDependencies();
    $this->assertEquals(['config' => ['workbench_access.access_scheme.editorial_section']], $dependencies);
  }

  /**
   * Tests scheme dependencies.
   */
  public function testSchemeDependencies() {
    $this->assertEquals([
      'config' => [
        'field.field.node.field_workbench_access',
        'node.type.page',
      ],
    ], $this->scheme->getDependencies());
    $this->assertEquals([
      'config' => [
        'system.menu.main',
        'node.type.page',
      ],
    ], $this->menuScheme->getDependencies());
  }

}
