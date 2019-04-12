<?php

namespace Drupal\workbench_access_protect\Access;

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
   * Use this to determine if the entity has children bundles.
   *
   * @param EntityInterface $entity
   *
   * @return bool
   *    TRUE if the entity has bundles, FALSE otherwise
   */
  public function hasBundles(EntityInterface $entity);

  /**
   * Check a specific bundle to determine if it is in use or not.
   * Use this to determine if this is the 'leaf' entity that actually
   * needs to check to see if is deletec.
   *
   * @param \Drupal\Core\Entity\EntityInterface $term
   *
   * @return bool
   *   TRUE
   */
  public function checkBundle(EntityInterface $entity);



}
