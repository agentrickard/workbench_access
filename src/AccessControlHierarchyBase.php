<?php

namespace Drupal\workbench_access;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginWithFormsTrait;
use Drupal\workbench_access\Entity\AccessSchemeInterface;
use Drupal\workbench_access\Plugin\views\filter\Section;
use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a base hierarchy class that others may extend.
 */
abstract class AccessControlHierarchyBase extends PluginBase implements AccessControlHierarchyInterface, ContainerFactoryPluginInterface {

  use PluginWithFormsTrait;
  use StringTranslationTrait;

  /*
   * A configuration factory object to store configuration.
   *
   * @var ConfigFactory
   */
  protected $configFactory;

  /**
   * The access tree array.
   *
   * @var array
   */
  protected $tree;

  /**
   * User section storage.
   *
   * @var \Drupal\workbench_access\UserSectionStorageInterface
   */
  protected $userSectionStorage;

  /**
   * Config for module.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

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
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, UserSectionStorageInterface $user_section_storage, ConfigFactoryInterface $configFactory, EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->config = $configFactory->get('workbench_access.settings');
    $this->userSectionStorage = $user_section_storage;
    $this->entityTypeManager = $entityTypeManager;
    $this->setConfiguration($configuration);
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
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = $configuration + $this->defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->pluginDefinition['id'];
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getTree() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function resetTree() {
    unset($this->tree);
  }

  /**
   * {@inheritdoc}
   */
  public function load($id) {
    $tree = $this->getTree();
    foreach ($tree as $parent => $data) {
      if (isset($data[$id])) {
        return $data[$id];
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $node_types = $this->entityTypeManager->getStorage('node_type')->loadMultiple();
    $form = [];
    /** @var \Drupal\node\NodeTypeInterface $type */
    foreach ($node_types as $id => $type) {
      $form['workbench_access_status_' . $id] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Enable Workbench Access control for @type content.', ['@type' => $type->label()]),
        '#description' => $this->t('If selected, all @type content will be subject to editorial access restrictions.', ['@type' => $type->label()]),
        '#default_value' => $type->getThirdPartySetting('workbench_access', 'workbench_access_status', 0),
      ];
      $options = ['' => $this->t('No field set')];
      $options += $this->getFields('node', $type->id());
      if (!empty($options)) {
        $form['field_' . $id] = [
          '#type' => 'select',
          '#title' => $this->t('Access control field'),
          '#options' => $options,
          '#default_value' => $this->fields('node', $type->id()),
        ];
      }
      else {
        $form['field_' . $id] = [
          '#type' => 'markup',
          '#markup' => $this->t('There are no eligible fields on this content type.'),
        ];
      }
    }
    return $form;
  }

  /**
   * Gets the fields that may be used for a plugin type.
   *
   * This method informs the system what fields are eligible to use for
   * access controls. For instance, with taxonomy, it returns all taxonomy
   * reference fields.
   *
   * @param $entity_type
   *   The type of entity access control is being tested for (e.g. 'node').
   * @param $bundle
   *   The entity bundle being tested (e.g. 'article').
   *
   * @return array
   *   An array of fields in the format id => label, for use in a form.
   */
  abstract protected function getFields($entity_type, $bundle);

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Default implementation is empty.
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $fields = $this->config->get('fields');

    $node_types = $this->entityTypeManager->getStorage('node_type')->loadMultiple();
    /** @var \Drupal\node\NodeTypeInterface $type */
    foreach ($node_types as $id => $type) {
      $field = $form_state->getValue('field_' . $id);
      if (!empty($field)) {
        $type->setThirdPartySetting('workbench_access', 'workbench_access_status', $form_state->getValue('workbench_access_status_' . $id));
        $fields['node'][$id] = $field;
      }
      else {
        $type->setThirdPartySetting('workbench_access', 'workbench_access_status', 0);
        $fields['node'][$id] = '';
      }
      $type->save();
    }
    return ['fields' => $fields];
  }

  /**
   * {@inheritdoc}
   */
  public function checkEntityAccess(AccessSchemeInterface $scheme, EntityInterface $entity, $op, AccountInterface $account, WorkbenchAccessManagerInterface $manager) {
    // @TODO: Check for super-admin?
    // We don't care about the View operation right now.
    if ($op === 'view' || $op === 'view label' || $account->hasPermission('bypass workbench access')) {
      // Return early.
      return AccessResult::neutral();
    }

    if (!$this->applies($entity->getEntityTypeId(), $entity->bundle())) {
      return AccessResult::neutral();
    }

    if ($fields = $this->fields($entity->getEntityTypeId(), $entity->bundle())) {
      // Discover the field and check status.
      $entity_sections = $this->getEntityValues($entity);
      // If no value is set on the entity, ignore.
      // @TODO: Is this the correct logic? It is helpful for new installs.
      $deny_on_empty = $this->config->get('deny_on_empty');

      if (!$deny_on_empty && empty($entity_sections)) {
        return AccessResult::neutral();
      }
      $user_sections = $this->userSectionStorage->getUserSections($scheme, $account->id());
      if (empty($user_sections)) {
        return AccessResult::forbidden();
      }
      // Check the tree status of the $entity against the $user.
      // Return neutral if in tree, forbidden if not.
      if (WorkbenchAccessManager::checkTree($scheme, $entity_sections, $user_sections)) {
        return AccessResult::neutral();
      }
      return AccessResult::forbidden();
    }
    // Deny is our default response.
    return AccessResult::forbidden();
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityValues(EntityInterface $entity) {
    $field = $this->fields($entity->getEntityTypeId(), $entity->bundle());
    if (!$entity instanceof ContentEntityInterface) {
      return [];
    }
    $values = [];
    foreach ($entity->get($field)->getValue() as $item) {
      if (isset($item['target_id'])) {
        $values[] = $item['target_id'];
      }
    }
    return $values;
  }

  /**
   * {inheritdoc}
   */
  protected function fields($entity_type, $bundle) {
    $fields = $this->config->get('fields');
    return isset($fields[$entity_type][$bundle]) ? $fields[$entity_type][$bundle] : [];
  }

  /**
   * {inheritdoc}
   */
  protected function fieldsByEntityType($entity_type) {
    // User/users do not name the data table consistently.
    if ($entity_type == 'user' || $entity_type == 'users') {
      return ['user' => WorkbenchAccessManagerInterface::FIELD_NAME];
    }
    else {
      $fields = $this->config->get('fields');
      return $fields[$entity_type];
    }
  }

  /**
   * {inheritdoc}
   */
  public function disallowedOptions($field) {
    $options = [];
    if (isset($field['widget']['#default_value']) && isset($field['widget']['#options'])) {
      $options = array_diff_key(array_flip($field['widget']['#default_value']), $field['widget']['#options']);
    }
    return array_keys($options);
  }

  /**
   * {inheritdoc}
   */
  public static function submitEntity(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\workbench_access\Entity\AccessSchemeInterface $access_scheme */
    foreach (\Drupal::entityTypeManager()->getStorage('access_scheme')->loadMultiple() as $access_scheme) {
      $scheme = $access_scheme->getAccessScheme();
      $hidden_values = $form_state->getValue(['workbench_access_disallowed', $access_scheme->id()]);
      if (!empty($values)) {

        $entity = $form_state->getFormObject()->getEntity();
        $scheme->massageFormValues($entity, $form_state, $hidden_values);
      }
    }
  }

  /**
   * {inheritdoc}
   */
  public function getViewsJoin($table, $key, $alias = NULL) {
    $fields = $this->fieldsByEntityType($table);
    $table_prefix = $table;
    $field_suffix = '_target_id';
    if ($table == 'users') {
      $table_prefix = 'user';
      $field_suffix = '_value';
    }
    foreach ($fields as $field) {
      if (!empty($field)) {
        $configuration[$field] = [
         'table' => $table_prefix . '__' . $field,
         'field' => 'entity_id',
         'left_table' => $table,
         'left_field' => $key,
         'operator' => '=',
         'table_alias' => $field,
         'real_field' => $field . $field_suffix,
        ];
      }
    }
    return $configuration;
  }

  /**
   * {inheritdoc}
   */
  public function addWhere(Section $filter, $values) {
    // The JOIN data tells us if we have multiple tables to deal with.
    $join_data = $this->getViewsJoin($filter->table, $filter->realField);
    if (count($join_data) == 1) {
      $filter->query->addWhere($filter->options['group'], "$filter->tableAlias.$filter->realField", array_values($values), $filter->operator);
    }
    else {
      $or = db_or();
      foreach ($join_data as $field => $data) {
        $alias = $data['table_alias'] . '.' . $data['real_field'];
        $or->condition($alias, array_values($values), $filter->operator);
      }
      $filter->query->addWhere($filter->options['group'], $or);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function viewsData(&$data, AccessSchemeInterface $scheme) {
    $data['node']['workbench_access_section'] = [
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

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(ContentEntityInterface $entity, FormStateInterface $form_state, array $hidden_values) {
    // Null op.
  }

}
