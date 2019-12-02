<?php

namespace Drupal\Tests\workbench_access\Functional;

use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\workbench_access\Traits\WorkbenchAccessTestTrait;

/**
 * Tests for deleting a role and removing associated data.
 *
 * @group workbench_access
 */
class DeleteRoleTest extends BrowserTestBase {

  use WorkbenchAccessTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'workbench_access',
    'node',
    'menu_ui',
    'link',
    'menu_link_content',
    'options',
    'user',
    'system',
  ];

  /**
   * Tests that deleting a role clears their data from storage.
   */
  public function testRoleDelete() {
    $this->setUpContentType();

    $scheme = $this->setUpMenuScheme(['page'], ['main']);

    $base_link = MenuLinkContent::create([
      'title' => 'Link 1',
      'link' => [['uri' => 'route:<front>']],
      'menu_name' => 'main',
    ]);
    $base_link->save();

    $section_id = $base_link->getPluginId();

    $this->setUpRole('role_a');
    $this->setUpRole('role_b');

    /* @var \Drupal\workbench_access\RoleSectionStorageInterface $role_section_storage */
    $role_section_storage = $this->container->get('workbench_access.role_section_storage');

    $role_section_storage->addRole($scheme, 'role_a', [$section_id]);
    $role_section_storage->addRole($scheme, 'role_b', [$section_id]);

    $assigned_roles = $role_section_storage->getRoles($scheme, $section_id);

    $this->assertEquals(['role_a', 'role_b'], $assigned_roles, 'The test roles are not assigned to the section.');
    
    /* @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $this->container->get('entity_type.manager');
    $role_storage = $entity_type_manager->getStorage('user_role');
    $role_b = $role_storage->load('role_b');
    $role_b->delete();

    $assigned_roles = $role_section_storage->getRoles($scheme, $section_id);

    $this->assertEquals(['role_a'], $assigned_roles, 'The remaining roles are not assigned to the section.');
  }

  /**
   * Sets up role that has access to content.
   *
   * @param string $name
   *   The machine name for the role: the role id.
   */
  public function setUpRole($name) {
    $this->createRole([
      'access administration pages',
      'create page content',
      'edit any page content',
      'administer menu',
      'delete any page content',
      'use workbench access',
    ], $name);
  }

}
