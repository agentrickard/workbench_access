<?php

namespace Drupal\Tests\workbench_access\Unit;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\user\UserInterface;
use Drupal\user\UserStorageInterface;
use Drupal\workbench_access\RoleSectionStorageInterface;
use Drupal\workbench_access\UserSectionStorage;
use Drupal\workbench_access\WorkbenchAccessManager;
use Drupal\workbench_access\WorkbenchAccessManagerInterface;

/**
 * Unit tests for user section storage service.
 *
 * @group workbench_access
 *
 * @coversDefaultClass \Drupal\workbench_access\UserSectionStorage
 */
class UserSectionStorageUnitTest extends UnitTestCase {

  /**
   * Tests that ::getUserSections is statically cached.
   *
   * @covers ::getUserSections
   */
  public function testGetUserSectionsShouldBeStaticallyCached() {
    $field_items = $this->prophesize(FieldItemListInterface::class);
    $field_items->getValue()->willReturn([
      ['value' => 123],
      ['value' => 456],
    ])->shouldBeCalledTimes(1);
    $user = $this->prophesize(UserInterface::class);
    $user->get(WorkbenchAccessManagerInterface::FIELD_NAME)->willReturn($field_items->reveal());
    $testUserId = 37;
    $user_storage = $this->prophesize(UserStorageInterface::class);
    // We shouldn't hit this code more than once if the static cache works.
    $user_storage->load($testUserId)->willReturn($user->reveal())->shouldBeCalledTimes(1);
    $entity_type_manager = $this->prophesize(EntityTypeManagerInterface::class);
    $entity_type_manager->getStorage('user')->willReturn($user_storage->reveal());
    $role_section_storage = $this->prophesize(RoleSectionStorageInterface::class);
    $role_section_storage->getRoleSections($user->reveal())->willReturn([]);
    $user_section_storage = new UserSectionStorage($entity_type_manager->reveal(), $user->reveal(), $role_section_storage->reveal());
    // First time, prime the cache.
    $user_section_storage->getUserSections($testUserId);
    // Second time, just return the result.
    $user_section_storage->getUserSections($testUserId);
  }

}
