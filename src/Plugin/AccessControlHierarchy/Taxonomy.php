<?php

namespace Drupal\workbench_access\Plugin\AccessControlHierarchy;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\taxonomy\VocabularyInterface;
use Drupal\workbench_access\AccessControlHierarchyBase;
use Drupal\workbench_access\Entity\AccessSchemeInterface;
use Drupal\workbench_access\UserSectionStorageInterface;
use Drupal\workbench_access\WorkbenchAccessManager;
use Drupal\workbench_access\WorkbenchAccessManagerInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a hierarchy based on a Vocabulary.
 *
 * @AccessControlHierarchy(
 *   id = "taxonomy",
 *   module = "taxonomy",
 *   entity = "taxonomy_term",
 *   label = @Translation("Taxonomy"),
 *   description = @Translation("Uses a taxonomy vocabulary as an access control hierarchy.")
 * )
 */
class Taxonomy extends AccessControlHierarchyBase {

  /**
   * Field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $bundleInfo;

  /**
   * Constructs a new AccessControlHierarchyBase object.
   *
   * @param array $configuration
   *   Configuration.
   * @param string $plugin_id
   *   Plugin ID.
   * @param mixed $plugin_definition
   *   Plugin definition.
   * @param \Drupal\workbench_access\UserSectionStorageInterface $user_section_storage
   *   User section storage.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   Entity field manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundleInfo
   *   Entity type bundle info.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, UserSectionStorageInterface $user_section_storage, ConfigFactoryInterface $configFactory, EntityTypeManagerInterface $entityTypeManager, EntityFieldManagerInterface $entityFieldManager, EntityTypeBundleInfoInterface $bundleInfo) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $user_section_storage, $configFactory, $entityTypeManager);
    $this->entityFieldManager = $entityFieldManager;
    $this->bundleInfo = $bundleInfo;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('workbench_access.user_section_storage'),
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * @inheritdoc
   */
  public function getTree() {
    if (!isset($this->tree)) {
      $this->tree = [];
      /** @var \Drupal\taxonomy\TermStorageInterface $term_storage */
      $term_storage = $this->entityTypeManager->getStorage('taxonomy_term');
      $tree = [];
      foreach ($this->configuration['vocabularies'] as $vocabulary_id) {
        if ($vocabulary = Vocabulary::load($vocabulary_id)) {
          $tree[$vocabulary_id][$vocabulary_id] = [
            'label' => $vocabulary->label(),
            'depth' => 0,
            'parents' => [],
            'weight' => 0,
            'description' => $vocabulary->label(),
          ];
          // @TODO: It is possible that this will return a filtered set, if
          // term_access is applied to the query.
          $data = $term_storage->loadTree($vocabulary_id);
          $this->tree = $this->buildTree($vocabulary_id, $data, $tree);
        }
      }
    }
    return $this->tree;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $defaults = [
      'fields' => [],
      'vocabularies' => [],
    ];
    return $defaults + parent::defaultConfiguration();
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
      $tree[$id][$term->tid] = [
        'id' => $term->tid,
        'label' => $term->name,
        'depth' => $term->depth + 1,
        'parents' => $this->convertParents($term, $id), // @TODO: This doesn't return what we want.
        'weight' => $term->weight,
        'description' => $term->description__value, // @TODO: security
      ];
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
   * {@inheritdoc}
   */
  protected function getFields($entity_type, $bundle) {
    $list = [];
    // @todo use the entity field manager.
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
  public function alterOptions(AccessSchemeInterface $scheme, $field, array $user_sections = []) {
    $element = $field;
    if (isset($element['widget']['#options'])) {
      foreach ($element['widget']['#options'] as $id => $data) {
        $sections = [$id];
        if (empty(WorkbenchAccessManager::checkTree($scheme, $sections, $user_sections))) {
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
          $element['widget'][$key]['target_id']['#selection_handler'] = 'workbench_access:taxonomy_term:' . $scheme->id();
        }
      }
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function applies($entity_type_id, $bundle) {
    return (bool) $this->getApplicableFields($entity_type_id, $bundle);
  }

  /**
   * {@inheritdoc}
   */
  protected function fields($entity_type, $bundle) {
    return array_column($this->getApplicableFields($entity_type, $bundle), 'field');
  }

  /**
   * Gets applicable fields for given entity type and bundle.
   *
   * @param string $entity_type
   *   Entity type ID.
   * @param string $bundle
   *   Bundle ID.
   *
   * @return array
   *   Associative Array of fields with keys entity_type, bundle and field.
   */
  protected function getApplicableFields($entity_type, $bundle) {
    return array_filter($this->configuration['fields'], function ($field) use ($entity_type, $bundle) {
      $field += [
        'entity_type' => NULL,
        'bundle' => NULL,
        'field_name' => '',
      ];
      return $field['entity_type'] === $entity_type && $field['bundle'] === $bundle;
    });
  }

  /**
   * {@inheritdoc}
   */
  public function viewsData(&$data, AccessSchemeInterface $scheme) {
    foreach (array_column($this->configuration['fields'], 'entity_type') as $entity_type_id) {
      $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
      if ($base_table = $entity_type->getBaseTable()) {
        $data[$base_table]['workbench_access_section'] = [
          'title' => t('Workbench Section @name', ['@name' => $scheme->label()]),
          'help' => t('The sections to which this content belongs in the @name scheme.', [
            '@name' => $scheme->label(),
          ]),
          'field' => [
            'id' => 'workbench_access_section:' . $scheme->id(),
          ],
          'filter' => [
            'field' => 'nid',
            'id' => 'workbench_access_section:' . $scheme->id(),
          ],
        ];
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(ContentEntityInterface $entity, FormStateInterface $form_state, array $hidden_values) {
    foreach ($this->fields($entity->getEntityTypeId(), $entity->bundle()) as $field_name) {
      $values = $form_state->getValue($field_name);
      foreach ($hidden_values as $value) {
        $values[]['target_id'] = $value;
      }
      $form_state->setValue($field_name, $values);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['vocabularies'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Vocabularies'),
      '#description' => $this->t('Select the vocabularies to use for access control'),
      '#default_value' => $this->configuration['vocabularies'],
      '#options' => array_map(function (VocabularyInterface $vocabulary) {
        return $vocabulary->label();
      }, $this->entityTypeManager->getStorage('taxonomy_vocabulary')->loadMultiple()),
    ];
    $entity_reference_fields = $this->entityFieldManager->getFieldMapByFieldType('entity_reference');
    $taxonomy_fields = [];
    foreach ($entity_reference_fields as $entity_type_id => $fields) {
      foreach ($fields as $field_name => $details) {
        foreach ($details['bundles'] as $bundle) {
          $field_definitions = $this->entityFieldManager->getFieldDefinitions($entity_type_id, $bundle);
          if (isset($field_definitions[$field_name]) && $field_definitions[$field_name]->getFieldStorageDefinition()->getSetting('target_type') === 'taxonomy_term') {
            $taxonomy_fields[$entity_type_id][$bundle][$field_name] = $field_definitions[$field_name]->getLabel();
          }
        }
      }
    }
    if (!$taxonomy_fields) {
      $form['fields'] = ['#markup' => $this->t('There are no configured taxonomy fields, please create a new term reference field to continue')];
      return $form;
    }
    $form['fields'] = [];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    return parent::submitConfigurationForm($form, $form_state); // TODO: Change the autogenerated stub
  }

}
