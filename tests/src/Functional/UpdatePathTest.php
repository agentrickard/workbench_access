<?php

namespace Drupal\Tests\workbench_access\Functional;

use Drupal\block\Entity\Block;
use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Drupal\workbench_access\WorkbenchAccessManagerInterface;

/**
 * Defines a class for testing the update path to scheme based access.
 *
 * @group workbench_access
 */
class UpdatePathTest extends UpdatePathTestBase {

  protected $defaultTheme = 'classy';

  /**
   * Set database dump files to be used.
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../fixtures/workbench_access.update-hook-test.php.gz',
      __DIR__ . '/../../fixtures/workbench_access.block-placement.php',
    ];
  }

  /**
   * Tests workbench_access_update_8002, 8003(), and 8004().
   */
  public function testUpdatePath() {
    $expected_new_config = [
      'deny_on_empty' => \Drupal::config('workbench_access.settings')->get('deny_on_empty'),
      '_core' => \Drupal::config('workbench_access.settings')->get('_core'),
    ];
    $block = Block::load('workbench_access_block');
    $this->assertTrue(empty($block->get('settings')['context_mapping']));
    $this->runUpdates();

    // Tests that the first update was run.
    // workbench_access_post_update_convert_to_scheme()
    $this->assertEquals($expected_new_config, \Drupal::config('workbench_access.settings')->getRawData());
    $this->assertEquals('default', $this->container->get('state')->get('workbench_access_upgraded_scheme_id'));
    $entity_type_manager = $this->container->get('entity_type.manager');

    $block = $entity_type_manager->getStorage('block')->loadUnchanged('workbench_access_block');
    $this->assertEquals(['node' => '@node.node_route_context:node'], $block->get('settings')['context_mapping']);

    // Checks that schemes have been converted to new storage.
    /** @var \Drupal\workbench_access\Entity\AccessSchemeInterface $scheme */
    $scheme = $entity_type_manager->getStorage('access_scheme')->load('default');
    $this->assertEquals('Section', $scheme->label());
    $this->assertEquals('Sections', $scheme->getPluralLabel());
    $this->assertEquals('taxonomy', $scheme->getAccessScheme()->getPluginId());
    $this->assertEquals([
      'fields' => [
        [
          'entity_type' => 'node',
          'bundle' => 'article',
          'field' => 'field_tags',
        ],
      ],
      'vocabularies' => ['tags'],
    ], $scheme->getAccessScheme()->getConfiguration());

    // Test that user storage was updated.
    // workbench_access_post_update_section_user_association().
    $user_storage = $this->container->get('workbench_access.user_section_storage');
    $role_storage = $this->container->get('workbench_access.role_section_storage');
    $terms = $entity_type_manager->getStorage('taxonomy_term')->loadByProperties([
      'name' => 'section 1',
    ]);
    $term = reset($terms);
    $users = $entity_type_manager->getStorage('user')->loadByProperties([
      'name' => 'robbo',
    ]);
    $user = reset($users);
    $editors = $user_storage->getEditors($scheme, $term->id());
    $this->assertNotEmpty($editors);
    $this->assertEquals([$user->id() => 'robbo'], $editors);
    $sections = $user_storage->getUserSections($scheme, $user);
    $this->assertNotEmpty($sections);
    $this->assertContains($term->id(), $sections);

    // Test that role storage was updated.
    // workbench_access_post_update_section_role_association().
    $roles = $role_storage->getRoles($scheme, $term->id());
    $this->assertNotEmpty($roles);
    $this->assertEquals(['editors'], $roles);

    // Test that views data is present.
    $views_data = $this->container->get('views.views_data')->getAll();
    $this->assertNotEmpty($views_data['node']['workbench_access_section']);
    $this->assertEquals('default', $views_data['node']['workbench_access_section']['field']['scheme']);
    $this->assertEquals('default', $views_data['node']['workbench_access_section']['filter']['scheme']);
    $this->assertEquals('default', $views_data['users']['workbench_access_section']['field']['scheme']);
    $this->assertEquals('default', $views_data['users']['workbench_access_section']['filter']['scheme']);
    $this->assertNotEmpty($views_data['users']['workbench_access_section']);

    // Ensure the field was deleted by
    // workbench_access_post_update_workbench_access_field_delete().
    $field_storage = \Drupal::entityTypeManager()->getStorage('field_config');
    $field = $field_storage->load(WorkbenchAccessManagerInterface::FIELD_NAME);
    $this->assertEmpty($field);
    $field_storage_config = \Drupal::entityTypeManager()->getStorage('field_storage_config');
    $field = $field_storage_config->load(WorkbenchAccessManagerInterface::FIELD_NAME);
    $this->assertEmpty($field);

    // Explicit test for https://www.drupal.org/project/workbench_access/issues/2946766
    $this->drupalGet('user/register');

    // Test hook_workbench_access_scheme_update_alter().
    $installer = \Drupal::service('module_installer');
    $installer->install(['workbench_access_hooks']);
    $settings = [];
    $config = \Drupal::state()->get('workbench_access_original_configuration', FALSE);
    \Drupal::moduleHandler()->alter('workbench_access_scheme_update', $settings, $config);
    $expected = ['test' => 'test'];
    $this->assertEquals($expected, $settings);
  }

}
