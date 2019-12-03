<?php

namespace Drupal\Tests\workbench_access\Kernel;

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\KernelTests\KernelTestBase;
use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\system\Entity\Menu;
use Drupal\taxonomy\Entity\Term;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\Tests\workbench_access\Traits\WorkbenchAccessTestTrait;
use Drupal\workbench_access\Entity\AccessScheme;

/**
 * Tests workbench_access integration with tokens.
 *
 * @group workbench_access
 */
class SectionTokenTest extends KernelTestBase {

  use UserCreationTrait;
  use WorkbenchAccessTestTrait;
  use ContentTypeCreationTrait;

  /**
   * Access menu.
   *
   * @var \Drupal\system\MenuInterface
   */
  protected $menu;

  /**
   * Access vocabulary.
   *
   * @var \Drupal\taxonomy\VocabularyInterface
   */
  protected $vocabulary;

  /**
   * Taxonomy scheme.
   *
   * @var \Drupal\workbench_access\Entity\AccessSchemeInterface
   */
  protected $taxonomyScheme;

  /**
   * Menu scheme.
   *
   * @var \Drupal\workbench_access\Entity\AccessSchemeInterface
   */
  protected $menuScheme;

  /**
   * User section storage.
   *
   * @var \Drupal\workbench_access\UserSectionStorage
   */
  protected $userStorage;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'link',
    'menu_link_content',
    'menu_ui',
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
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(['filter', 'node', 'workbench_access', 'system']);
    $this->installEntitySchema('taxonomy_term');
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installEntitySchema('menu_link_content');
    $this->installEntitySchema('section_association');
    $this->installSchema('system', ['key_value', 'sequences']);
    $this->taxonomyScheme = AccessScheme::create([
      'id' => 'editorial_section',
      'label' => 'Editorial section',
      'plural_label' => 'Editorial sections',
      'scheme' => 'taxonomy',
      'scheme_settings' => [
        'vocabularies' => ['workbench_access'],
        'fields' => [
          [
            'entity_type' => 'taxonomy_term',
            'bundle' => 'tags',
            'field' => 'field_workbench_access',
          ],
        ],
      ],
    ]);
    $this->taxonomyScheme->save();
    $this->vocabulary = $this->setUpVocabulary();
    $node_type = $this->createContentType(['type' => 'page']);
    // This is created by system module.
    $this->menu = Menu::load('main');
    $node_type->setThirdPartySetting('menu_ui', 'available_menus', ['main']);
    $node_type->save();
    $this->menuScheme = $this->setupMenuScheme([$node_type->id()], ['main'], 'menu_section');
    $this->userStorage = \Drupal::service('workbench_access.user_section_storage');
  }

  /**
   * Tests the user section tokens.
   */
  public function testUserSectionTokens() {
    $user = $this->createUser();
    $link = MenuLinkContent::create([
      'title' => 'Test menu link',
      'link' => [['uri' => 'route:<front>']],
      'menu_name' => $this->menu->id(),
    ]);
    $link->save();
    $this->userStorage->addUser($this->menuScheme, $user, [$link->getPluginId()]);

    $tokens = [
      'workbench-access-sections' => 'Test menu link',
    ];
    $bubbleable_metadata = new BubbleableMetadata();
    $this->assertTokens('user', ['user' => $user], $tokens, $bubbleable_metadata);
    $this->assertContains($this->menuScheme->getCacheTags()[0], $bubbleable_metadata->getCacheTags());

    $term = Term::create([
      'name' => 'Test term',
      'vid' => $this->vocabulary->id(),
    ]);
    $term->save();
    $this->userStorage->addUser($this->taxonomyScheme, $user, [$term->id()]);
    $tokens = [
      'workbench-access-sections' => 'Test term, Test menu link',
    ];
    $this->assertTokens('user', ['user' => $user], $tokens, $bubbleable_metadata);
    $this->assertContains($this->taxonomyScheme->getCacheTags()[0], $bubbleable_metadata->getCacheTags());
    $term = Term::create([
      'name' => 'Test term 2',
      'vid' => $this->vocabulary->id(),
    ]);
    $term->save();
    $this->userStorage->addUser($this->taxonomyScheme, $user, [$term->id()]);
    $tokens = [
      'workbench-access-sections' => 'Test term, Test term 2, Test menu link',
    ];
    $this->assertTokens('user', ['user' => $user], $tokens, $bubbleable_metadata);

    $this->setCurrentUser($user);
    $this->assertTokens('current-user', [], $tokens, $bubbleable_metadata);
  }

  /**
   * Helper function to assert tokens.
   *
   * @param string $type
   *   The token type.
   * @param array $data
   *   The input data.
   * @param array $tokens
   *   The tokens.
   * @param \Drupal\Core\Render\BubbleableMetadata $bubbleable_metadata
   *   The cache metadata.
   *
   * @return array
   *   The array of replacements.
   */
  protected function assertTokens($type, array $data, array $tokens, BubbleableMetadata $bubbleable_metadata) {
    $input = array_reduce(array_keys($tokens), function ($carry, $token) use ($type) {
      $carry[$token] = "[$type:$token]";
      return $carry;
    }, []);

    $replacements = \Drupal::token()->generate($type, $input, $data, [], $bubbleable_metadata);
    foreach ($tokens as $name => $expected) {
      $token = $input[$name];
      $this->assertEquals($replacements[$token], $expected);
    }

    return $replacements;
  }

}
