<?php

namespace Drupal\Tests\workbench_access\Unit;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryAggregateInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\user\UserInterface;
use Drupal\user\UserStorageInterface;
use Drupal\workbench_access\Entity\AccessSchemeInterface;
use Drupal\workbench_access\RoleSectionStorageInterface;
use Drupal\workbench_access\UserSectionStorage;

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
    $user = $this->prophesize(UserInterface::class);
    $testUserId = 37;
    $user_storage = $this->prophesize(UserStorageInterface::class);
    // We shouldn't hit this code more than once if the static cache works.
    $user_storage->load($testUserId)->willReturn($user->reveal())->shouldBeCalledTimes(1);
    $entity_type_manager = $this->prophesize(EntityTypeManagerInterface::class);
    $entity_type_manager->getStorage('user')->willReturn($user_storage->reveal());
    $section_storage = $this->prophesize(EntityStorageInterface::class);
    $query = $this->prophesize(QueryAggregateInterface::class);
    $section_storage->getAggregateQuery()->willReturn($query->reveal());
    $query->condition('access_scheme', 'editorial_section')->willReturn($query->reveal());
    $query->condition('user_id', 37)->willReturn($query->reveal());
    $query->groupBy('section_id')->willReturn($query->reveal());
    $query->execute()->willReturn([37 => ['section_id' => 3]]);
    $entity_type_manager->getStorage('section_association')->willReturn($section_storage->reveal());
    $scheme = $this->prophesize(AccessSchemeInterface::class);
    $scheme->id()->willReturn('editorial_section');
    $role_section_storage = $this->prophesize(RoleSectionStorageInterface::class);
    $role_section_storage->getRoleSections($scheme->reveal(), $user->reveal())->willReturn([]);
    $user_section_storage = new UserSectionStorage($entity_type_manager->reveal(), $user->reveal(), $role_section_storage->reveal());
    // First time, prime the cache.
    $user_section_storage->getUserSections($scheme->reveal(), $user->reveal());
    // Second time, just return the result.
    $user_section_storage->getUserSections($scheme->reveal(), $user->reveal());
  }

}
