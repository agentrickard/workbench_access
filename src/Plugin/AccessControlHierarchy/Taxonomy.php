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
          'parent' => 0,
          'weight' => 0,
          'description' => $vocabulary->label(),
        );
        $data = \Drupal::entityManager()->getStorage('taxonomy_term')->loadTree($id);
        foreach ($data as $term) {
          $tree[$id][$term->tid] = array(
            'label' => $term->name,
            'depth' => $term->depth + 1,
            'parent' => current($term->parents),
            'weight' => $term->weight,
            'description' => $term->description__value, // @TODO: security
          );
        }
      }
    }
    return $tree;
  }

}
