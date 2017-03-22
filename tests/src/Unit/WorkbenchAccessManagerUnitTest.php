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
use Drupal\workbench_access\WorkbenchAccessManager;
use Drupal\workbench_access\WorkbenchAccessManagerInterface;

/**
 * Unit tests for workbench access manager.
 *
 * @group workbench_access
 *
 * @coversDefaultClass \Drupal\workbench_access\WorkbenchAccessManager
 */
class WorkbenchAccessManagerUnitTest extends UnitTestCase {

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
    $user->getRoles()->willReturn(['editor']);
    $user->get(WorkbenchAccessManagerInterface::FIELD_NAME)->willReturn($field_items->reveal());
    $testUserId = 37;
    $state = $this->prophesize(StateInterface::class);
    $state->get(WorkbenchAccessManagerInterface::WORKBENCH_ACCESS_ROLES_STATE_PREFIX . 'editor', [])->willReturn([5]);
    $user_storage = $this->prophesize(UserStorageInterface::class);
    // We shouldn't hit this code more than once if the static cache works.
    $user_storage->load($testUserId)->willReturn($user)->shouldBeCalledTimes(1);
    $entity_type_manager = $this->prophesize(EntityTypeManagerInterface::class);
    $entity_type_manager->getStorage('user')->willReturn($user_storage->reveal());
    $workbench_access_manger = new WorkbenchAccessManager($this->getMock(\Traversable::class), $this->getMock(CacheBackendInterface::class), $this->getMock(ModuleHandlerInterface::class), $entity_type_manager->reveal(), $state->reveal());
    // First time, prime the cache.
    $workbench_access_manger->getUserSections($testUserId);
    // Second time, just return the result.
    $workbench_access_manger->getUserSections($testUserId);
  }

}
