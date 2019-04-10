<?php

namespace Drupal\workbench_access\Access;

use Drupal\Core\Entity\EntityInterface;

/**
 * Class TaxonomyDeleteAccessCheck.
 *
 * @package Drupal\workbench_access\Access
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
   * @param EntityInterface $entity
   *
   * @return mixed
   */
  public function hasBundles(EntityInterface $entity);

  public function getBundles();

  /**
   * Check a specific bundle to determine if it is in use or not.
   *
   * @param \Drupal\Core\Entity\EntityInterface $term
   *
   * @return mixed
   */
  public function checkBundle(EntityInterface $term);



}
