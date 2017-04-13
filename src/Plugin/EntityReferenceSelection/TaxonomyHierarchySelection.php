<?php

namespace Drupal\workbench_access\Plugin\EntityReferenceSelection;

use Drupal\Component\Utility\Html;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;
use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\taxonomy\Plugin\EntityReferenceSelection\TermSelection;

/**
 * Provides specific access control for the taxonomy_term entity type.
 *
 * @EntityReferenceSelection(
 *   id = "workbench_access:taxonomy_term",
 *   label = @Translation("Restricted Taxonomy Term selection"),
 *   entity_types = {"taxonomy_term"},
 *   group = "workbench_access",
 *   weight = 1,
 *   base_plugin_label = @Translation("Workbench Access: Restricted term selection")
 * )
 */
class TaxonomyHierarchySelection extends TermSelection {

  /**
   * {@inheritdoc}
   */
  public function getReferenceableEntities($match = NULL, $match_operator = 'CONTAINS', $limit = 0) {
    // Get the base options list from the normal handler. We will filter later.
    if ($match || $limit) {
      $options = parent::getReferenceableEntities($match , $match_operator, $limit);
    }
    else {
      $options = array();

      $bundles = $this->entityManager->getBundleInfo('taxonomy_term');
      $handler_settings = $this->configuration['handler_settings'];
      $bundle_names = !empty($handler_settings['target_bundles']) ? $handler_settings['target_bundles'] : array_keys($bundles);

      foreach ($bundle_names as $bundle) {
        if ($vocabulary = Vocabulary::load($bundle)) {
          if ($terms = $this->entityManager->getStorage('taxonomy_term')->loadTree($vocabulary->id(), 0, NULL, TRUE)) {
            foreach ($terms as $term) {
              $options[$vocabulary->id()][$term->id()] = str_repeat('-', $term->depth) . Html::escape($this->entityManager->getTranslationFromContext($term)->label());
            }
          }
        }
      }
    }
    // Now, filter the options by permission.
    // If assigned to the top level or a superuser, no alteration.
    $account = \Drupal::currentUser();
    if ($account->hasPermission('bypass workbench access')) {
      return $options;
    }
    // Check each section for access.
    $manager = \Drupal::getContainer()->get('plugin.manager.workbench_access.scheme');
    $user_sections = $manager->getUserSections($account->id());
    foreach ($options as $key => $values) {
      if ($manager->checkTree([$key], $user_sections)) {
        continue;
      }
      else {
        foreach ($values as $id => $value) {
          if (!$manager->checkTree([$id], $user_sections)) {
            unset($options[$key][$id]);
          }
        }
      }
    }

    return $options;
  }

}
