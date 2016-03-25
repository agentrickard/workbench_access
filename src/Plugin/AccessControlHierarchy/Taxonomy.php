<?php

/**
 * @file
 * Contains \Drupal\workbench_access\Plugin\AccessControlHierarchy\Taxonomy.
 */

namespace Drupal\workbench_access\Plugin\AccessControlHierarchy;

use Drupal\workbench_access\AccessControlHierarchyBase;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Defines a hierarchy based on a Vocaulary.
 *
 * @AccessControlHierarchy(
 *   id = "taxonomy",
 *   module = "taxonomy",
 *   base_entity = "taxonomy_vocabulary",
 *   entity = "taxonomy_term",
 *   label = @Translation("Taxonomy"),
 *   description = @Translation("Uses a taxonomy vocabulary as an access control hierarchy.")
 * )
 */
class Taxonomy extends AccessControlHierarchyBase {

  /**
   * @inheritdoc
   */
  public function getTree() {
    $config = $this->config('workbench_access.settings');
    $parents = $config->get('parents');
    $tree = array();
    foreach ($parents as $id => $label) {
      if ($vocabulary = Vocabulary::load($id)) {
        $tree[$id][$id] = array(
          'label' => $vocabulary->label(),
          'depth' => 0,
          'parents' => 0,
          'weight' => 0,
          'description' => $vocabulary->label(),
        );
        $data = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($id);
        foreach ($data as $term) {
          $tree[$id][$term->tid] = array(
            'id' => $term->tid,
            'label' => $term->name,
            'depth' => $term->depth + 1,
            'parents' => $term->parents,
            'weight' => $term->weight,
            'description' => $term->description__value, // @TODO: security
          );
        }
      }
    }
    return $tree;
  }

  /**
   * @inheritdoc
   */
  public function getFields($entity_type, $bundle, $parents) {
    $list = [];
    $query = \Drupal::entityQuery('field_config')
      ->condition('status', 1)
      ->condition('entity_type', $entity_type)
      ->condition('bundle', $bundle)
      ->condition('field_type', 'entity_reference')
      ->sort('label')
      ->execute();
    $fields = \Drupal::entityManager()->getStorage('field_config')->loadMultiple(array_keys($query));
    foreach ($fields as $id => $field) {
      $handler = $field->getSetting('handler');
      $settings = $field->getSetting('handler_settings');
      if (substr_count($handler, 'taxonomy_term') > 0) {
        foreach ($settings['target_bundles'] as $key => $target) {
          if (isset($parents[$key])) {
            $list[$field->getName()] = $field->label();
          }
        }
      }
    }
    return $list;
  }

}
