<?php

namespace Drupal\workbench_access\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\taxonomy\TermInterface;
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
class TaxonomyDeleteAccessCheck implements AccessInterface {

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
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  private $entityTypeManager;

  public function __construct(AccountInterface $account,
                              CurrentRouteMatch $route,
                              UserSectionStorage $userSectionStorage,
                              EntityFieldManager $fieldManager,
                              MessengerInterface $messenger,
                              EntityTypeManager $entityTypeManager) {

    $this->account = $account;
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
   * Determine if this term may be deleted.
   *
   * @return bool
   *   TRUE if it may be deleted, FALSE otherwise.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function isDeleteAllowed() {
    /** @var \Drupal\taxonomy\TermInterface $term */
    $term = $this->getRouteEntity();

    $retval = TRUE;

    if ($term instanceof TermInterface) {
      $hasAccessControlMembers = $this->doesTermHaveMembers($term);
      $assigned_content = $this->isAssignedToContent($term);

      /*
       * If this term does not have users assigned to it for access
       * control, and the term is not assigned to any pieces of content,
       * it is OK to delete it.
       */
      if ($hasAccessControlMembers || $assigned_content) {

        $override_allowed = $this->account->hasPermission('allow taxonomy term delete');

        if ($assigned_content && !$override_allowed) {
          $this->messenger->addWarning(t("The term %term is being used to tag content and may not be deleted.",
            ['%term' => $term->getName()]));
          $retval = FALSE;
        }
        elseif ($assigned_content) {
          $this->messenger->addWarning(t("The term %term is being used to tag content.",
            ['%term' => $term->getName()]));
          $retval = TRUE;
        }

        if ($hasAccessControlMembers) {
          $this->messenger->addWarning(t("The term %term is being used for access control and may not be deleted.",
           ['%term' => $term->getName()]));
          $retval = FALSE;
        }

      }
    }

    return $retval;

  }

  /**
   * Determines if this term has active members in it.
   *
   * @return bool
   *   TRUE if the term has members, FALSE otherwise.
   */
  private function doesTermHaveMembers(TermInterface $term) {

    /** @var array $sections */
    $sections = $this->getActiveSections($term);

    if (count($sections) > 0) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Helper function to extract the entity for the supplied route.
   *
   * @return null|TermInterface
   *   A term interface object if the term exists. NULL otherwise.
   */
  private function getRouteEntity() {
    $route_match = $this->route;
    // Entity will be found in the route parameters.
    if (($route = $route_match->getRouteObject()) && ($parameters = $route->getOption('parameters'))) {
      // Determine if the current route represents an entity.
      foreach ($parameters as $name => $options) {
        if (isset($options['type']) && strpos($options['type'], 'entity:') === 0) {
          $entity = $route_match->getParameter($name);
          if ($entity instanceof TermInterface) {
            return $entity;
          }

          // Since entity was found, no need to iterate further.
          return NULL;
        }
      }
    }
  }

  /**
   * Inspect the given taxonomy term.
   *
   * This will determine if there are any active users assigned to it.
   *
   * @param \Drupal\taxonomy\TermInterface $term
   *   The Taxonomy Term to inspect.
   *
   * @return array
   *   An array of the users assigned to this section.
   */
  private function getActiveSections(TermInterface $term) {
    /** @var \Drupal\workbench_access\UserSectionStorageInterface $sectionStorage */
    $sectionStorage = $this->userSectionStorage;

    /** @var \Drupal\Core\Config\Entity\ConfigEntityStorage $scheme */
    $scheme = $this->entityTypeManager->getStorage('access_scheme');

//    $access = $scheme->load("access_section");
    ////
    ////    $sections = $sectionStorage->getEditors($access, $term->id());
    ////
    ////    return $sections;
    $editors = array_reduce($this->entityTypeManager->getStorage('access_scheme')->loadMultiple(),
      function (array $editors, AccessSchemeInterface $scheme) use ($sectionStorage, $term) {
      $editors += $sectionStorage->getEditors($scheme, $term->id());
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
   * @param \Drupal\taxonomy\TermInterface $term
   *   The Taxonomy Term to inspect.
   *
   * @return bool
   *   TRUE if content is assigned to this term.
   *   FALSE if content is not assigned to this term.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function isAssignedToContent(TermInterface $term) {

    $map = $this->fieldManager->getFieldMap();

    foreach ($map as $entity_type => $fields) {
      foreach ($fields as $name => $field) {
        if ($field['type'] == 'entity_reference') {
          // Get the entity reference and determine if it's a taxonomy.
          /** @var \Drupal\field\Entity\FieldStorageConfig $fieldConfig */
          $fieldConfig = FieldStorageConfig::loadByName($entity_type, $name);
          if ($fieldConfig instanceof FieldStorageConfig &&
            $fieldConfig->getSettings()['target_type'] === 'taxonomy_term') {
            $entities = \Drupal::entityTypeManager()->getStorage($entity_type)->loadByProperties([
              $name => $term->id(),
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
