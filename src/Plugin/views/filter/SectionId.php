<?php

namespace Drupal\workbench_access\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\filter\ManyToOne;
use Drupal\views\Views;
use Drupal\views\ManyToOneHelper;
use Drupal\workbench_access\Entity\AccessSchemeInterface;
use Drupal\workbench_access\UserSectionStorageInterface;
use Drupal\workbench_access\WorkbenchAccessManager;
use Drupal\workbench_access\WorkbenchAccessManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Filter by assigned section.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("workbench_access_section_id")
 */
class SectionId extends ManyToOne {

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
   * Sets manager.
   *
   * @param \Drupal\workbench_access\WorkbenchAccessManagerInterface $manager
   *   Manager.
   *
   * @return $this
   */
  public function setManager(WorkbenchAccessManagerInterface $manager) {
    $this->manager = $manager;
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
  public function setUserSectionStorage(UserSectionStorageInterface $userSectionStorage) {
    $this->userSectionStorage = $userSectionStorage;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['operator']['default'] = 'in';
    $options['value']['default'] = ['All'];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function getValueOptions() {
    if (isset($this->valueOptions)) {
      return $this->valueOptions;
    }
    $schemes = \Drupal::entityTypeManager()->getStorage('access_scheme')->loadMultiple();
    foreach ($schemes as $scheme) {
      $tree = $scheme->getAccessScheme()->getTree();
      foreach ($tree as $items) {
        foreach ($items as $id => $item) {
          $options[$id] = str_repeat('-', $item['depth']) . ' ' . $item['label'];
        }
      }
    }
    $this->valueOptions = $options;
    return $this->valueOptions;
  }

  /**
   * {@inheritdoc}
   */
  public function operators() {
    $operators = [
      'in' => [
        'title' => $this->t('Is one of'),
        'short' => $this->t('in'),
        'short_single' => $this->t('='),
        'method' => 'opSimple',
        'values' => 1,
      ],
      'not in' => [
        'title' => $this->t('Is not one of'),
        'short' => $this->t('not in'),
        'short_single' => $this->t('<>'),
        'method' => 'opSimple',
        'values' => 1,
      ],
    ];
    return $operators;
  }

}
