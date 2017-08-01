<?php

namespace Drupal\workbench_access;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
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
  }

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
  public function status() {
    return $this->config->get('scheme') === $this->id();
  }

  /**
   * @inheritdoc
   */
  public function options() {
    if ($entity_type = $this->pluginDefinition['base_entity']) {
      return array_map(function (EntityInterface $entity) {
        return $entity->label();
      }, $this->entityTypeManager->getStorage($entity_type)->loadMultiple());
    }
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
  public function configForm($parents = []) {
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
      $options += $this->getFields('node', $type->id(), $parents);
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
   * {@inheritdoc}
   */
  public function configValidate(array &$form, FormStateInterface $form_state) {
    // Default implementation is empty.
  }

  /**
   * {@inheritdoc}
   */
  public function configSubmit(array &$form, FormStateInterface $form_state) {
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
  public function checkEntityAccess(EntityInterface $entity, $op, AccountInterface $account, WorkbenchAccessManagerInterface $manager) {
    // @TODO: Check for super-admin?
    // We don't care about the View operation right now.
    if ($op === 'view' || $op === 'view label' || $account->hasPermission('bypass workbench access')) {
      // Return early.
      return AccessResult::neutral();
    }

    // Check that the content type is configured.
    // @TODO: Right now this only handles nodes.
    /** @var \Drupal\node\NodeTypeInterface $type */
    $active = FALSE;
    if ($type = $this->entityTypeManager->getStorage('node_type')->load($entity->bundle())) {
      $active = $type->getThirdPartySetting('workbench_access', 'workbench_access_status', 0);
    }

    if (!$active) {
      // No such node-type or not-active.
      return AccessResult::neutral();
    }

    if ($field = $this->fields('node', $type->id())) {
      // Discover the field and check status.
      $entity_sections = $this->getEntityValues($entity, $field);
      // If no value is set on the entity, ignore.
      // @TODO: Is this the correct logic? It is helpful for new installs.
      $deny_on_empty = $this->config->get('deny_on_empty');

      if (!$deny_on_empty && empty($entity_sections)) {
        return AccessResult::neutral();
      }
      $user_sections = $this->userSectionStorage->getUserSections($account->id());
      if (empty($user_sections)) {
        return AccessResult::forbidden();
      }
      // Check the tree status of the $entity against the $user.
      // Return neutral if in tree, forbidden if not.
      if ($manager->checkTree($entity_sections, $user_sections)) {
        return AccessResult::neutral();
      }
      return AccessResult::forbidden();
    }
    // Deny is our default response.
    return AccessResult::forbidden();
  }

  /**
   * {@inheritdoc}
   *
   * @TODO: find a better way to deal with the $manager.
   * @TODO: write tests.
   */
  public function checkModerationAccess(EntityInterface $entity, AccountInterface $account, WorkbenchAccessManagerInterface $manager) {
    // @TODO: Move this duplicate code to a method.
    $active = FALSE;
    /** @var \Drupal\node\NodeTypeInterface $type */
    if ($type = $this->entityTypeManager->getStorage('node_type')->load($entity->bundle())) {
      $active = $type->getThirdPartySetting('workbench_access', 'workbench_access_status', 0);
    }
    if (!$active) {
      return TRUE;
    }
    // Check that the user can update the entity.
    $accessResult = $this->checkEntityAccess($entity, 'update', $account, $manager);
    // Our access check only returns Neutral or Forbidden, so we return the reverse of
    // isForbidden() since we are setting #access to true or false on the element.
    // See workbench_access_entity_view_alter().
    return !$accessResult->isForbidden();
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityValues(EntityInterface $entity, $field) {
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
  public function fields($entity_type, $bundle) {
    $fields = $this->config->get('fields');
    return isset($fields[$entity_type][$bundle]) ? $fields[$entity_type][$bundle] : [];
  }

  /**
   * {inheritdoc}
   */
  public function fieldsByEntityType($entity_type) {
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
    $values = $form_state->getValue('workbench_access_disallowed');
    if (!empty($values)) {
      $manager = \Drupal::getContainer()->get('plugin.manager.workbench_access.scheme');
      if ($scheme = $manager->getActiveScheme()) {
        $node = $form_state->getFormObject()->getEntity();
        $field = $scheme->fields('node', $node->bundle());
        $entity_values = $form_state->getValue($field);
      }
      foreach ($values as $value) {
        $entity_values[]['target_id'] = $value;
      }
      $form_state->setValue($field, $entity_values);
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

}
