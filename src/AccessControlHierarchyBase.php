<?php

/**
 * @file
 * Contains \Drupal\workbench_access\AccessControlHierarchyBase.
 */

namespace Drupal\workbench_access;

use Drupal\workbench_access\AccessControlHierarchyInterface;
use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeTypeInterface;
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
   * Returns the status of a hierarchy.
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
   * Gets the entire hierarchy tree.
   *
   * @return array
   */
  public function getTree() {
    return array();
  }

  /**
   * Loads a hierarchy definition for a single item in the tree.
   *
   * @param $id
   *   The identifier for the item, such as a term id.
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
   * Provides configuration options.
   */
  public function configForm($scheme, $parents = array()) {
    $node_types = \Drupal::entityManager()->getStorage('node_type')->loadMultiple();
    foreach ($node_types as $id => $type) {
      $form['workbench_access_status_' . $id] = array(
        '#type' => 'checkbox',
        '#title' => t('Enable Workbench Access control for @type content.', array('@type' => $type->label())),
        '#description' => t('If selected, all @type content will be subject to editorial access restrictions.', array('@type' => $type->label())),
        '#default_value' => $type->getThirdPartySetting('workbench_access', 'workbench_access_status', 0),
      );
      $options = $scheme->getFields('node', $type->id(), $parents);
      if (!empty($options)) {
        $form['field_' . $id] = array(
          '#type' => 'select',
          '#title' => $this->t('Access control field'),
          '#options' => $options,
          '#default_value' => $this->fields('node', $type->id()),
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
   * Validates configuration options.
   */
  public function configValidate() {
    return array();
  }

  /**
   * Submits configuration options.
   */
  public function configSubmit(array &$form, FormStateInterface $form_state) {
    $config = $this->config('workbench_access.settings');
    $fields = $config->get('fields');

    $node_types = \Drupal::entityManager()->getStorage('node_type')->loadMultiple();
    foreach ($node_types as $id => $type) {
      $field = $form_state->getValue('field_' . $id);
      if (!empty($field)) {
        $type->setThirdPartySetting('workbench_access', 'workbench_access_status', $form_state->getValue('workbench_access_status_' . $id));
        $fields['node'][$id][$field] = $field;
      }
      else {
        $type->setThirdPartySetting('workbench_access', 'workbench_access_status', 0);
        if(isset($fields['node'][$id])) {
          unset($fields['node'][$id]);
        }
      }
      $type->save();
    }
    return ['fields' => $fields];
  }

  /**
   * Returns the service container.
   *
   * This method is marked private to prevent sub-classes from retrieving
   * services from the container through it. Instead,
   * \Drupal\Core\DependencyInjection\ContainerInjectionInterface should be used
   * for injecting services.
   *
   * @return \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container.
   */
  private function container() {
    return \Drupal::getContainer();
  }

  /**
   * Returns the requested cache bin.
   *
   * @param string $bin
   *   (optional) The cache bin for which the cache object should be returned,
   *   defaults to 'default'.
   *
   * @return \Drupal\Core\Cache\CacheBackendInterface
   *   The cache object associated with the specified bin.
   */
  protected function cache($bin = 'default') {
    return $this->container()->get('cache.' . $bin);
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
      $this->configFactory = $this->container()->get('config.factory');
    }
    return $this->configFactory->get($name);
  }

  /**
   * Returns the access control fields used by the plugin.
   */
  public function fields($entity_type, $bundle) {
    $config = $this->config('workbench_access.settings');
    $fields = $config->get('fields');
    return $fields[$entity_type][$bundle];
  }

}
