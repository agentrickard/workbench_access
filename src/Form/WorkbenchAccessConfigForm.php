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
use Drupal\workbench_access\WorkbenchAccessManagerInterface;

/**
 * Configure Workbench access settings for this site.
 */
class WorkbenchAccessConfigForm extends ConfigFormBase {

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
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state keyvalue collection to use.
   * @param \Drupal\workbench_access\WorkbenchAccessManagerInterface
   *   The Workbench Access hierarchy manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, StateInterface $state, WorkbenchAccessManagerInterface $manager) {
    parent::__construct($config_factory);
    $this->state = $state;
    $this->manager = $manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('state'),
      $container->get('plugin.manager.workbench_access.scheme')
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
    $schemes = $this->manager->getSchemes();
    if (empty($schemes)) {
      $form['error'] = array(
        '#type' => 'item',
        '#title' => t('Error'),
        '#markup' => t('There are no available access schemes to configure.'),
      );
    }
    else {
      $form['scheme'] = array(
        '#type' => 'radios',
        '#title' => t('Active access scheme'),
        '#options' => $schemes,
        '#default_value' => $config->get('scheme', ''),
      );
      foreach ($schemes as $id => $label) {
        $scheme = $this->manager->getScheme($id);
        $form['parents'][$id] = array(
          '#type' => 'checkboxes',
          '#title' => t('!label editorial access options', array('!label' => $label)),
          '#options' => $scheme->options(),
          '#default_value' => $config->get('parents', array()),
          '#states' => array(
            'visible' => array(
            ':input[name=scheme]' => array('value' => $id),
            ),
          ),
          '#description' => t('Select the !label options to be used for access control.', array('!label' => $label)),
        );
      }
    }
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
    $scheme = $form_state->getValue('scheme');
    $this->config('workbench_access.settings')
      ->set('scheme', $scheme)
      ->set('parents', array_filter($form_state->getValue($scheme)))
      ->set('label', $form_state->getValue('label'))
      ->set('plural_label', $form_state->getValue('plural_label'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
