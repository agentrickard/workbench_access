<?php

namespace Drupal\workbench_access_protect\Access;

use Drupal\Core\Entity\EntityInterface;

/**
 * Class DeleteAccessCheck.
 */
interface DeleteAccessCheckInterface {


  /**
   * Determine if this entity may be deleted.
   *
   * @param EntityInterface $entity
   *   The term to check.
   *
   * @return bool
   *   TRUE if it may be deleted, FALSE otherwise.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function isDeleteAllowed(EntityInterface $entity);

  /**
   * Determine if this entity has bundles or not.
   *
   * Use this to determine if the entity has children bundles.
   *
   * @param EntityInterface $entity
   *
   * @return bool
   *    TRUE if the entity has bundles, FALSE otherwise
   */
  public function hasBundles(EntityInterface $entity);

  /**
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *
   * @return mixed
   */
  public function getBundles(EntityInterface $entity);

  /**
   * Check an entire bundle to see if any of the bundle's
   * entities are being actively used for access control.
   *
   * @param string $bundle
   *   The bundle ID
   * @param string $entity
   *   The entity id
   *
   * @return bool
   *   TRUE
   */
   public function isDeleteAllowedBundle($bundle, $entity);


}
