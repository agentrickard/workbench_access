<?php

namespace Drupal\workbench_access_protect\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\EntityInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\workbench_access\UserSectionStorage;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\workbench_access\Entity\AccessSchemeInterface;

/**
 * Class DeleteAccessCheck.
 */
class DeleteAccessCheck implements DeleteAccessCheckInterface {

  /**
   * Default object for current_route_match service.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  private $route;

  /**
   * A class for storing and retrieving sections assigned to a user.
   *
   * @var \Drupal\workbench_access\UserSectionStorage
   */
  private $userSectionStorage;

  /**
   * Manages the discovery of entity fields.
   *
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  private $fieldManager;

  /**
   * Manages entity type plugin definitions.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  private $entityTypeManager;

  /**
   * An interface for an entity type bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  private $entityTypeBundleInfo;

  /**
   * Constructs a new DeleteAccessCheck.
   */
  public function __construct(CurrentRouteMatch $route,
                              UserSectionStorage $userSectionStorage,
                              EntityFieldManager $fieldManager,
                              EntityTypeManager $entityTypeManager,
                              EntityTypeBundleInfoInterface $entityTypeBundleInfo) {

    $this->route = $route;
    $this->userSectionStorage = $userSectionStorage;
    $this->fieldManager = $fieldManager;
    $this->entityTypeManager = $entityTypeManager;
    $this->entityTypeBundleInfo = $entityTypeBundleInfo;

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
   * {@inheritdoc}
   */
  public function isDeleteAllowed(EntityInterface $entity) {
    $return = TRUE;

    // If this entity does not have users assigned to it for access control and
    // is not assigned to any pieces of content, it is OK to delete it.
    $assigned_members = $this->hasMembers($entity);
    if ($assigned_members) {
      return FALSE;
    }

    // Breaking up the IF statement is a performance gain, since most sites
    // have more nodes than users.
    $assigned_content = $this->hasContent($entity);
    if ($assigned_content) {
      $return = FALSE;
    }

    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function getBundles(EntityInterface $entity) {
    $bundle = $this->entityTypeManager->getDefinitions()[$entity->getEntityTypeId()]->get('bundle_of');
    $bundles = $this->entityTypeBundleInfo->getBundleInfo($bundle);
    return array_combine(array_keys($bundles), array_keys($bundles));
  }

  /**
   * {@inheritdoc}
   */
  public function hasBundles(EntityInterface $entity) {
    return !is_null($this->entityTypeManager->getDefinitions()[$entity->getEntityTypeId()]->get('bundle_of'));
  }

  /**
   * {@inheritdoc}
   */
  public function isDeleteAllowedBundle($bundle, $entity) {
    $bundle_of = $this->entityTypeManager->getDefinitions()[$entity->getEntityTypeId()]->get('bundle_of');
    $entity_id_key = $this->entityTypeManager->getDefinitions()[$entity->getEntityTypeId()]->get('entity_keys')['id'];
    $entities = $this->entityTypeManager->getStorage($bundle_of)->loadByProperties(
      [$entity_id_key => $bundle]
    );

    /*
     * Cycle through the entities of this bundle. As soon as one is discovered
     * as being actively used for access control, we can deny delete.
     */
    foreach ($entities as $bundle) {
      if ($this->isDeleteAllowed($bundle) === FALSE) {
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * Determines if this term has active members in it.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to inspect.
   *
   * @return bool
   *   TRUE if the term has members, FALSE otherwise.
   */
  private function hasMembers(EntityInterface $entity) {
    return (bool) (count($this->getActiveSections($entity)) > 0);
  }

  /**
   * Inspects the given entity for active users.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to inspect.
   *
   * @return array
   *   An array of the users assigned to this section.
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
   * Inspects the given entity for active content.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to inspect.
   *
   * @return bool
   *   TRUE if content is assigned to this entity.
   *   FALSE if content is not assigned to this entity.
   */
  private function hasContent(EntityInterface $entity) {
    foreach ($this->getAllReferenceFields($entity) as $name => $fieldConfig) {
      // Get the entity reference and determine if it is access controlled.
      if ($fieldConfig instanceof FieldStorageConfig) {
        // Base query.
        $entities = \Drupal::entityQuery($fieldConfig->get('entity_type'));
        // Check all children, if required.
        // @TODO: allow this to be a configurable setting.
        // @TODO: test coverage.
        $storage = \Drupal::entityTypeManager()->getStorage($entity->getEntityTypeId());
        if (method_exists($storage, 'loadChildren')) {
          $children = $storage->loadChildren($entity->id());
          if ($children) {
            $ids = array_keys($children);
            $ids[] = $entity->id();
            $entities->condition($fieldConfig->get('field_name'), $ids, 'IN');
            $children_checked = TRUE;
          }
        }
        // Else just query this entity.
        if (empty($children_checked)) {
          $entities->condition($fieldConfig->get('field_name'), $entity->id());
        }
        $result = $entities->range(0, 1)->execute();
        if (count($result) > 0) {
          return TRUE;
        }
      }
      // @TODO: check for menu field handling.
    }

    return FALSE;
  }

  /**
   * Get all entities with referencing fields targeting the ID.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to inspect.
   *
   * @return array
   *   An array of entities with referencing fields targeting the given ID.
   */
  private function getAllReferenceFields(EntityInterface $entity) {
    // First, we are going to try to retrieve a cached instance.
    $found_fields = \Drupal::cache()->get('workbench_access_protect');
    if ($found_fields === FALSE) {
      $found_fields = [];
      $scheme_storage = \Drupal::entityTypeManager()
        ->getStorage('access_scheme');
      // Grab the fields configured for each access scheme and then check
      // that they are within our list of protected entity types.
      foreach ($scheme_storage->loadMultiple() as $scheme) {
        $access_scheme = $scheme->getAccessScheme();
        $scheme_type = $scheme->get('scheme');
        $protect_list = workbench_access_protect_list($scheme_type);
        if (in_array($entity->getEntityTypeId(), $protect_list, TRUE)) {
          $configuration = $access_scheme->getConfiguration();
          foreach ($configuration['fields'] as $field_info) {
            $fieldConfig = FieldStorageConfig::loadByName($field_info['entity_type'], $field_info['field']);
            if ($fieldConfig) {
              $found_fields[$field_info['field']] = $fieldConfig;
            }
          }
        }
      }
    }
    else {
      $found_fields = $found_fields->data;
    }

    \Drupal::cache()
      ->set('workbench_access_protect', $found_fields, \Drupal::time()->getRequestTime() + 60);

    return $found_fields;
  }

  /**
   * {@inheritdoc}
   */
  public function isAccessControlled(EntityInterface $entity) {
    $schemes = $this->entityTypeManager->getStorage('access_scheme')->loadMultiple();
    /** @var \Drupal\workbench_access\Entity\AccessScheme $scheme */
    foreach ($schemes as $scheme) {
      foreach ($scheme->getAccessScheme()->getConfiguration() as $config) {
        if (in_array($entity->bundle(), $config)) {
          return TRUE;
        }
      }
    }
    return FALSE;
  }

}
