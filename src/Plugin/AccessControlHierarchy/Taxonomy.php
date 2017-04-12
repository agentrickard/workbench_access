<?php

namespace Drupal\workbench_access\Plugin\AccessControlHierarchy;

use Drupal\workbench_access\AccessControlHierarchyBase;
use Drupal\workbench_access\WorkbenchAccessManagerInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Defines a hierarchy based on a Vocabulary.
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
    if (!isset($this->tree)) {
      $parents = $this->config->get('parents');
      $tree = array();
      foreach ($parents as $id => $label) {
        if ($vocabulary = Vocabulary::load($id)) {
          $tree[$id][$id] = array(
            'label' => $vocabulary->label(),
            'depth' => 0,
            'parents' => [],
            'weight' => 0,
            'description' => $vocabulary->label(),
          );
          // @TODO: It is possible that this will return a filtered set, if
          // term_access is applied to the query.
          $data = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($id);
          $this->tree = $this->buildTree($id, $data, $tree);
        }
      }
    }
    return $this->tree;
  }

  /**
   * Traverses the taxonomy tree and builds parentage arrays.
   *
   * Note: this method is necessary to load all parents to the array.
   *
   * @param $id
   *   The root id of the section tree.
   * @param array $data
   *   An array of menu tree or subtree data.
   * @param array &$tree
   *   The computed tree array to return.
   *
   * @return array $tree
   *   The compiled tree data.
   */
  protected function buildTree($id, $data, &$tree) {
    foreach ($data as $term) {
      $tree[$id][$term->tid] = array(
        'id' => $term->tid,
        'label' => $term->name,
        'depth' => $term->depth + 1,
        'parents' => $this->convertParents($term, $id), // @TODO: This doesn't return what we want.
        'weight' => $term->weight,
        'description' => $term->description__value, // @TODO: security
      );
      foreach ($tree[$id][$term->tid]['parents'] as $key) {
        if (!empty($tree[$id][$key]['parents'])) {
          $tree[$id][$term->tid]['parents'] = array_unique(array_merge($tree[$id][$key]['parents'], $tree[$id][$term->tid]['parents']));
        }
      }
    }
    return $tree;
  }

  /**
   * Coverts the 0 parent id to a string.
   *
   * @param $term
   *   The term to modify.
   * @param $id
   *   The root parent id string.
   */
  private function convertParents($term, $id) {
    foreach ($term->parents as $pos => $parent) {
      if ($parent === 0 || $parent === '0') {
        $term->parents[$pos] = $id;
      }
    }
    return $term->parents;
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
    $fields = \Drupal::entityTypeManager()->getStorage('field_config')->loadMultiple(array_keys($query));
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

  /**
   * {@inheritdoc}
   */
  public function alterOptions($field, WorkbenchAccessManagerInterface $manager, array $user_sections = []) {
    $element = $field;
    if (isset($element['widget']['#options'])) {
      foreach ($element['widget']['#options'] as $id => $data) {
        $sections = [$id];
        if (empty($manager->checkTree($sections, $user_sections))) {
          unset($element['widget']['#options'][$id]);
        }
      }
    }
    // Check for autocomplete fields. In this case, we replace the selection
    // handler with our own, which likely breaks Views-based handlers, but that
    // can be handled later. We swap out the default handler for our own, since
    // we don't have another way to filter the autocomplete results.
    // @TODO: test this against views-based handlers.
    // @see \Drupal\workbench_access\Plugin\EntityReferenceSelection\TaxonomyHierarchySelection
    else {
      foreach ($element['widget'] as $key => $item) {
        if (is_array($item) && isset($item['target_id']['#type']) && $item['target_id']['#type'] == 'entity_autocomplete') {
          $element['widget'][$key]['target_id']['#selection_handler'] = 'workbench_access:taxonomy_term';
        }
      }
    }

    return $element;
  }

}
