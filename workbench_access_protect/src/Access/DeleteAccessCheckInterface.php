<?php

namespace Drupal\workbench_access_protect\Access;

use Drupal\Core\Entity\EntityInterface;

/**
 * Class DeleteAccessCheck.
 */
interface DeleteAccessCheckInterface {

  /**
   * Determines if this entity may be deleted.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to check.
   *
   * @return bool
   *   TRUE if it may be deleted, FALSE otherwise.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function isDeleteAllowed(EntityInterface $entity);

  /**
   * Determines if this entity has bundles or not.
   *
   * Use this to determine if the entity has children bundles.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The term to check for bundles.
   *
   * @return bool
   *   TRUE if the entity has bundles, FALSE otherwise
   */
  public function hasBundles(EntityInterface $entity);

  /**
   * Gets the assigned bundles associated with an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to get bundles on.
   *
   * @return array
   *   An array where the keys and values are bundle ids.
   */
  public function getBundles(EntityInterface $entity);

  /**
   * Checks if a bundle's entities are being actively used for access control.
   *
   * @param string $bundle
   *   The bundle ID.
   * @param string $entity
   *   The entity id.
   *
   * @return bool
   *   TRUE
   */
  public function isDeleteAllowedBundle($bundle, $entity);

  /**
   * Determines if the entity is used for access control.
   *
   * This method is used to determine if we should actually perform the usage
   * check or skip.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   An entity object.
   *
   * @return bool
   *   TRUE if used, FALSE otherwise
   */
  public function isAccessControlled(EntityInterface $entity);

}
