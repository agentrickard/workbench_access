<?php

/**
 * @file
 * Contains \Drupal\workbench_access\Form\WorkbenchAccessByRoleForm.
 */

namespace Drupal\workbench_access\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Form\FormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\workbench_access\WorkbenchAccessManagerInterface;

/**
 * Configure Workbench access settings for this site.
 */
class WorkbenchAccessByRoleForm extends FormBase {

  /**
   * The Workbench Access manager service.
   *
   * @var \Drupal\workbench_access\WorkbenchAccessManager
   */
  protected $manager;

  /**
   * The state keyvalue collection.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructs a new WorkbenchAccessConfigForm.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state keyvalue collection to use.
   * @param \Drupal\workbench_access\WorkbenchAccessManagerInterface
   *   The Workbench Access hierarchy manager.
   */
  public function __construct(StateInterface $state, WorkbenchAccessManagerInterface $manager) {
    $this->state = $state;
    $this->manager = $manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('state'),
      $container->get('plugin.manager.workbench_access.scheme')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'workbench_access_by_role';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $id = NULL) {
    $element = $this->manager->getElement($id);
    kint($element);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

}
