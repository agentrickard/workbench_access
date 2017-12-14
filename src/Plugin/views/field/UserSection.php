<?php

namespace Drupal\workbench_access\Plugin\views\field;

use Drupal\workbench_access\Entity\AccessSchemeInterface;
use Drupal\workbench_access\Plugin\views\field\Section;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use Drupal\workbench_access\WorkbenchAccessManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Field handler to present the section assigned to the user.
 *
 * This is a very simple handler, mainly for testing.
 * @TODO: Convert this to using a proper multi-value handler.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("workbench_access_user_section")
 */
class UserSection extends Section {

  /**
   * Scheme.
   *
   * @var \Drupal\workbench_access\Entity\AccessSchemeInterface
   */
  protected $scheme;

  /**
   * Manager.
   *
   * @var \Drupal\workbench_access\WorkbenchAccessManagerInterface
   */
  protected $manager;

  /**
   * User storage.
   *
   * @var \Drupal\workbench_access\UserSectionStorageInterface
   */
  protected $userSectionStorage;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var self $instance */
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->additional_fields['uid'] = 'uid';
    $instance->aliases['uid'] = 'uid';
    return $instance->setScheme($container->get('entity_type.manager')->getStorage('access_scheme')->load($configuration['scheme']))
      ->setManager($container->get('plugin.manager.workbench_access.scheme'))
      ->setUserSectionStorage($container->get('workbench_access.user_section_storage'));
  }

  /**
   * Sets manager.
   *
   * @param \Drupal\workbench_access\WorkbenchAccessManagerInterface $manager
   *   Manager.
   *
   * @return $this
   */
  public function setManager($manager) {
    $this->manager = $manager;
    return $this;
  }

  /**
   * Sets access scheme.
   *
   * @param \Drupal\workbench_access\Entity\AccessSchemeInterface $scheme
   *   Access scheme.
   *
   * @return $this
   */
  public function setScheme(AccessSchemeInterface $scheme) {
    $this->scheme = $scheme;
    return $this;
  }

  /**
   * Sets user section storage.
   *
   * @param \Drupal\workbench_access\UserSectionStorageInterface $userSectionStorage
   *   User section storage.
   *
   * @return $this
   */
  public function setUserSectionStorage($userSectionStorage) {
    $this->userSectionStorage = $userSectionStorage;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $uid = $this->getValue($values, 'uid');
    $all = $this->scheme->getAccessScheme()->getTree();
    if ($this->manager->userInAll($this->scheme, $uid)) {
      $sections = WorkbenchAccessManager::getAllSections($this->scheme, TRUE);
    }
    else {
      $sections = $this->userSectionStorage->getUserSections($this->scheme, $uid);
    }
    $output = [];
    foreach ($sections as $id) {
      foreach ($all as $root => $data) {
        if (isset($data[$id])) {
          $output[] = $this->sanitizeValue($data[$id]['label']);
        }
      }
    }
    return trim(implode($this->options['separator'], $output), $this->options['separator']);
  }

}
