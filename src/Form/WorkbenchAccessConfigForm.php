<?php

/**
 * @file
 * Contains \Drupal\workbench_access\Form\WorkbenchAccessConfigForm.
 */

namespace Drupal\workbench_access\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Workbench access settings for this site.
 */
class WorkbenchAccessConfigForm extends ConfigFormBase {

  /**
   * The state keyvalue collection.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructs a new WorkbenchAccessConfigForm.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state keyvalue collection to use.
   */
  public function __construct(ConfigFactoryInterface $config_factory, StateInterface $state) {
    parent::__construct($config_factory);
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('state')
    );
  }


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'workbench_access_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['workbench_access.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('workbench_access.settings');
    $form['label'] = array(
      '#type' => 'textfield',
      '#size' => 32,
      '#title' => t('Access group label'),
      '#default_value' => $config->get('label', 'Section'),
      '#description' => t('Label shown to define a Workbench Access control group.'),
    );
    $form['plural_label'] = array(
      '#type' => 'textfield',
      '#size' => 32,
      '#title' => t('Access group label (plural form)'),
      '#default_value' => $config->get('plural_label', 'Sections'),
      '#description' => t('Label shown to define a set of Workbench Access control groups.'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('workbench_access.settings')
      ->set('label', $form_state->getValue('label'))
      ->set('plural_label', $form_state->getValue('plural_label'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
