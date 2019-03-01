<?php

namespace Drupal\workbench_access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\taxonomy\TermAccessControlHandler;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines the access control handler for the taxonomy term entity type.
 *
 * @see \Drupal\taxonomy\Entity\Term
 */
class WorkbenchAccessTermAccessControlHandler extends TermAccessControlHandler  {

  /**
   * @var \Drupal\workbench_access\Access\TaxonomyDeleteAccessCheck
   */
  private $accessCheck;

  public function __construct(EntityTypeInterface $entity_type) {
    parent::__construct($entity_type);

    $accessCheck = \Drupal::service('workbench_access.taxonomy_delete_access_check');
    $this->accessCheck = $accessCheck;

  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {

    if ($operation === 'delete') {
      // If this is a delete operation, we are first going to check to see
      // if there's a workbench access reason why this term should not be
      // deleted.
      if ($this->accessCheck->isDeleteAllowedForTerm($entity) === FALSE) {
        return AccessResult::forbidden();
      }
    }

    return parent::checkAccess($entity, $operation, $account);


  }


}
