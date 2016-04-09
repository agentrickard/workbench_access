<?php

/**
 * @file
 * Contains \Drupal\workbench_access\AccessControlHierarchyBase.
 */

namespace Drupal\workbench_access;

use Drupal\workbench_access\AccessControlHierarchyInterface;
use Drupal\workbench_access\WorkbenchAccessManager;
use Drupal\workbench_access\WorkbenchAccessManagerInterface;
use Drupal\node\NodeTypeInterface;
use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Defines a base hierarchy class that others may extend.
 */
abstract class AccessControlHierarchyBase extends PluginBase implements AccessControlHierarchyInterface {

  use StringTranslationTrait;

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
    $config = $this->config('workbench_access.settings');
    $scheme = $config->get('scheme');
    return $scheme == $this->id();
  }

  /**
   * @inheritdoc
   */
  public function options() {
    $options = array();
    if ($entity_type = $this->pluginDefinition['base_entity']) {
      $entities = entity_load_multiple($entity_type);
      foreach ($entities as $key => $entity) {
        $options[$key] = $entity->label();
      }
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function getTree() {
    return array();
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
  public function configForm($scheme, $parents = array()) {
    $node_types = \Drupal::entityTypeManager()->getStorage('node_type')->loadMultiple();
    foreach ($node_types as $id => $type) {
      $form['workbench_access_status_' . $id] = array(
        '#type' => 'checkbox',
        '#title' => t('Enable Workbench Access control for @type content.', array('@type' => $type->label())),
        '#description' => t('If selected, all @type content will be subject to editorial access restrictions.', array('@type' => $type->label())),
        '#default_value' => $type->getThirdPartySetting('workbench_access', 'workbench_access_status', 0),
      );
      $options = ['' => $this->t('No field set')];
      $options += $scheme->getFields('node', $type->id(), $parents);
      if (!empty($options)) {
        $form['field_' . $id] = array(
          '#type' => 'select',
          '#title' => $this->t('Access control field'),
          '#options' => $options,
          '#default_value' => $this->fields('node', $type->id()),
          '#description' => $this->t('Autocomplete fields are not supported.'),
        );
      }
      else {
        $form['field_' . $id] = array(
          '#type' => 'markup',
          '#markup' => $this->t('There are no eligible fields on this content type.'),
        );
      }
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function configValidate(array &$form, FormStateInterface $form_state) {
    // No default implementation.
  }

  /**
   * {@inheritdoc}
   */
  public function configSubmit(array &$form, FormStateInterface $form_state) {
    $config = $this->config('workbench_access.settings');
    $fields = $config->get('fields');

    $node_types = \Drupal::entityManager()->getStorage('node_type')->loadMultiple();
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
    // Deny is our default response.
    $return = AccessResult::forbidden();

    // Check that the content type is configured.
    // @TODO: Right now this only handles nodes.
    $type = \Drupal::entityTypeManager()->getStorage('node_type')->load($entity->bundle());
    $active = $type->getThirdPartySetting('workbench_access', 'workbench_access_status', 0);

    // Get the field data.
    $scheme = $manager->getActiveScheme();
    $field = $scheme->fields('node', $type->id());

    // @TODO: Check for super-admin?
    // We don't care about the View operation right now.
    if ($op == 'view' || $account->hasPermission('bypass workbench access')) {
      $return = AccessResult::neutral();
    }
    elseif ($active && !empty($scheme) && !empty($field)) {
      // Discover the field and check status.
      $entity_sections = $this->getEntityValues($entity, $field);
      // If no value is set on the entity, ignore.
      // @TODO: Is this the correct logic? It is helpful for new installs.
      if (empty($entity_sections)) {
        $return = AccessResult::neutral();
      }
      else {
        $user_sections = $manager->getUserSections($account->id);
        if (empty($user_sections)) {
          $return = AccessResult::forbidden();
        }
        // Check the tree status of the $entity against the $user.
        // Return neutral if in tree, forbidden if not.
        if ($manager->checkTree($entity_sections, $user_sections)) {
          $return = AccessResult::neutral();
        }
        else {
          $return = AccessResult::forbidden();
        }
      }
    }
    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityValues(EntityInterface $entity, $field) {
    $values = array();
    foreach ($entity->get($field)->getValue() as $item) {
      $values[] = $item['target_id'];
    }
    return $values;
  }

  /**
   * Returns the access control fields configured for use by the plugin.
   *
   * @param $entity_type
   *   The type of entity access control is being tested for (e.g. 'node').
   * @param $bundle
   *   The entity bundle being tested (e.g. 'article').
   */
  public function fields($entity_type, $bundle) {
    $config = $this->config('workbench_access.settings');
    $fields = $config->get('fields');
    return $fields[$entity_type][$bundle];
  }

  /**
   * Retrieves a configuration object.
   *
   * This is the main entry point to the configuration API. Calling
   * @code $this->config('book.admin') @endcode will return a configuration
   * object in which the book module can store its administrative settings.
   *
   * @param string $name
   *   The name of the configuration object to retrieve. The name corresponds to
   *   a configuration file. For @code \Drupal::config('book.admin') @endcode,
   *   the config object returned will contain the contents of book.admin
   *   configuration file.
   *
   * @return \Drupal\Core\Config\Config
   *   A configuration object.
   */
  protected function config($name) {
    if (!$this->configFactory) {
      $this->configFactory = \Drupal::getContainer()->get('config.factory');
    }
    return $this->configFactory->get($name);
  }

  /**
   * {inheritdoc}
   */
  public function disallowedOptions($field) {
    $options = array_diff_key(array_flip($field['widget']['#default_value']), $field['widget']['#options']);
    return array_keys($options);
  }

}
