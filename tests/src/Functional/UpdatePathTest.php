<?php

namespace Drupal\Tests\workbench_access\Functional;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Drupal\workbench_access\WorkbenchAccessManagerInterface;

/**
 * Defines a class for testing the update path to scheme based access.
 *
 * @group workbench_access
 */
class UpdatePathTest extends UpdatePathTestBase {

  /**
   * Set database dump files to be used.
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../fixtures/workbench_access.update-hook-test.php.gz',
    ];
  }

  /**
   * Tests workbench_access_update_8002().
   */
  public function testUpdatePath() {
    $this->runUpdates();
    $this->assertEquals('default', $this->container->get('state')->get('workbench_access_upgraded_scheme_id'));
    /** @var \Drupal\workbench_access\Entity\AccessSchemeInterface $scheme */
    $entity_type_manager = $this->container->get('entity_type.manager');
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
    $roles = $role_storage->getRoles($scheme, $term->id());
    $this->assertNotEmpty($roles);
    $this->assertEquals(['editors'], $roles);
    $views_data = $this->container->get('views.views_data')->getAll();
    $this->assertNotEmpty($views_data['node']['workbench_access_section']);
    $this->assertEquals('default', $views_data['node']['workbench_access_section']['field']['scheme']);
    $this->assertEquals('default', $views_data['node']['workbench_access_section']['filter']['scheme']);
    $this->assertEquals('default', $views_data['users']['workbench_access_section']['field']['scheme']);
    $this->assertEquals('default', $views_data['users']['workbench_access_section']['filter']['scheme']);
    $this->assertNotEmpty($views_data['users']['workbench_access_section']);

    // Ensure the field was deleted.
    $field_storage = \Drupal::entityTypeManager()->getStorage('field_config');
    $field = $field_storage->load(WorkbenchAccessManagerInterface::FIELD_NAME);
    $this->assertEmpty($field);
    $field_storage_config = \Drupal::entityTypeManager()->getStorage('field_storage_config');
    $field = $field_storage_config->load(WorkbenchAccessManagerInterface::FIELD_NAME);
    $this->assertEmpty($field);
  }

}
