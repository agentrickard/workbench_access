<?php

namespace Drupal\workbench_access_protect\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\EntityInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\workbench_access\UserSectionStorage;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\workbench_access\Entity\AccessSchemeInterface;

/**
 * Class TaxonomyDeleteAccessCheck.
 *
 * @package Drupal\workbench_access\Access
 */
class DeleteAccessCheck implements DeleteAccessCheckInterface {

  /**
   * @var \Drupal\Core\Session\AccountInterface
   */
  private $account;

  /**
   * @var \Drupal\Core\Routing\CurrentRouteMatch;
   */
  private $route;

  /**
   * @var \Drupal\workbench_access\UserSectionStorage
   */
  private $userSectionStorage;

  /**
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  private $fieldManager;


  /**
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  private $messenger;

  /**
   * @var array
   * A list of messages to be printed when access denied.
   */
  private $messages = [];

  /**
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  private $entityTypeManager;

  public function __construct(CurrentRouteMatch $route,
                              UserSectionStorage $userSectionStorage,
                              EntityFieldManager $fieldManager,
                              MessengerInterface $messenger,
                              EntityTypeManager $entityTypeManager) {

    $this->route = $route;
    $this->userSectionStorage = $userSectionStorage;
    $this->fieldManager = $fieldManager;
    $this->messenger = $messenger;
    $this->entityTypeManager = $entityTypeManager;

  }

  /**
   * This method is used to determine if it is OK to delete.
   *
   * The check is based on whether or not it is being actively used for access
   * control, and if content is assigned to it. If either of these statements
   * is true, then 'forbidden' will be returned to prevent the term
   * from being deleted.
   *
   * @return \Drupal\Core\Access\AccessResultAllowed|\Drupal\Core\Access\AccessResultForbidden
   *   Returns 'forbidden' if the term is being used for access control.
   *   Returns 'allowed' if the term is not being used for access control.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function access() {

    if ($this->isDeleteAllowed()) {
      return AccessResult::allowed();
    }

    return AccessResult::forbidden();
  }

  /**
   * @{inheritdoc}
   */
  public function isDeleteAllowed(EntityInterface $entity) {

    $retval = TRUE;

    if ($entity instanceof EntityInterface) {

      $hasAccessControlMembers = $this->doesTermHaveMembers($entity);
      $assigned_content = $this->isAssignedToContent($entity);

      /*
       * If this term does not have users assigned to it for access
       * control, and the term is not assigned to any pieces of content,
       * it is OK to delete it.
       */
      if ($hasAccessControlMembers || $assigned_content) {

        if ($assigned_content) {
          $retval = FALSE;
        }

        if ($hasAccessControlMembers) {
          $retval = FALSE;
        }
      }

    }

    return $retval;
  }

  public function hasBundles(EntityInterface $entity) {
    return \Drupal::service('entity_type.bundle.info')->getBundleInfo($entity->label());
  }

//  public function getBundles(EntityInterface $entity) {
//    return \Drupal::service('entity_type.bundle.info')->getBundleInfo($entity->label());
//  }

  /**
   * @{inheritdoc}
   */
  public function checkBundle(EntityInterface $term) {
    // TODO: Implement checkBundle() method.
  }

  /**
   * Determines if this term has active members in it.
   *
   * @return bool
   *   TRUE if the term has members, FALSE otherwise.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function doesTermHaveMembers(EntityInterface $entity) {

    /** @var array $sections */
    $sections = $this->getActiveSections($entity);

    if (count($sections) > 0) {
      return TRUE;
    }

    return FALSE;
  }


  /**
   * Inspect the given taxonomy term.
   *
   * This will determine if there are any active users assigned to it.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The Taxonomy Term to inspect.
   *
   * @return array
   *   An array of the users assigned to this section.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function getActiveSections(EntityInterface $entity) {
    /** @var \Drupal\workbench_access\UserSectionStorageInterface $sectionStorage */
    $sectionStorage = $this->userSectionStorage;

    $editors = array_reduce($this->entityTypeManager->getStorage('access_scheme')->loadMultiple(),
      function (array $editors, AccessSchemeInterface $scheme) use ($sectionStorage, $entity) {
      $editors += $sectionStorage->getEditors($scheme, $entity->id());
      return $editors;
    }, []);

    return $editors;
  }

  /**
   * Determine if tagged content exists.
   *
   * This method will determine if any entities exist in the system that are
   * tagged with the term.
   *
   * @param \Drupal\Core\Entity\EntityInterface $term
   *   The Entity to inspect.
   *
   * @return bool
   *   TRUE if content is assigned to this term.
   *   FALSE if content is not assigned to this term.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function isAssignedToContent(EntityInterface $entity) {

    $map = $this->fieldManager->getFieldMap();

    foreach ($map as $entity_type => $fields) {
      foreach ($fields as $name => $field) {
        if ($field['type'] == 'entity_reference') {
          // Get the entity reference and determine if it's a taxonomy.
          /** @var \Drupal\field\Entity\FieldStorageConfig $fieldConfig */
          $fieldConfig = FieldStorageConfig::loadByName($entity_type, $name);
          if ($fieldConfig instanceof FieldStorageConfig) {
            $entities = \Drupal::entityTypeManager()->getStorage($entity_type)->loadByProperties([
              $name => $entity->id(),
            ]);
            if (count($entities) > 0) {
              return TRUE;
            }
          }
        }
      }
    }

    return FALSE;

  }

}
