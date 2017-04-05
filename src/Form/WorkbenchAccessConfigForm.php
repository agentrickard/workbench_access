<?php

namespace Drupal\workbench_access\Form;

use Drupal\workbench_access\WorkbenchAccessManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
        '#title' => $this->t('Error'),
        '#markup' => $this->t('There are no available access schemes to configure.'),
      );
    }
    else {
      $form['scheme'] = array(
        '#type' => 'details',
        '#title' => $this->t('Active access scheme'),
        '#open' => TRUE,
      );
      $form['scheme']['scheme'] = array(
        '#type' => 'radios',
        '#title' => $this->t('Active access scheme'),
        '#options' => $schemes,
        '#default_value' => $config->get('scheme'),
      );
      $form['scheme']['reset_scheme'] = array(
        '#type' => 'checkbox',
        '#default_value' => FALSE,
        '#title' => $this->t('Reset assigned fields, user and role sections'),
        '#states' => array(
          'invisible' => array(
            ':input[name=scheme]' => array('value' => $config->get('scheme')),
          ),
        ),
        '#description' => $this->t('When switching access schemes, flush current field settings, user and role permissions. Recommended.'),
      );
      foreach ($schemes as $id => $label) {
        $scheme = $this->manager->getScheme($id);
        $form['scheme']['parents'][$id] = array(
          '#type' => 'checkboxes',
          '#title' => $this->t('@label editorial access options', array('@label' => $label)),
          '#options' => $scheme->options(),
          '#default_value' => $config->get('parents', array()),
          '#states' => array(
            'visible' => array(
              ':input[name=scheme]' => array('value' => $id),
            ),
          ),
          '#description' => $this->t('Select the @label options to be used for access control.', array('@label' => $label)),
        );
      }
      $form['scheme']['set'] = array(
        '#type' => 'submit',
        '#value' => $this->t('Set active scheme'),
        '#submit' => array('::submitActiveScheme'),
      );
    }
    if ($id = $config->get('scheme')) {
      $scheme = $this->manager->getScheme($id);
      $custom = $this->manager->getActiveScheme()->configForm($config->get('parents', array()));
    }
    if (!empty($custom)) {
      $form['custom'] = array(
        '#type' => 'details',
        '#title' => $this->t('Scheme settings'),
        '#open' => TRUE,
        '#markup' => '<strong>' . $this->t('These settings must be confirmed after saving the scheme and options above.') . '</strong>',
      );
      $form['custom'] += $custom;
    }
    $form['labels'] = array(
      '#type' => 'details',
      '#title' => $this->t('Labels'),
    );
    $form['labels']['label'] = array(
      '#type' => 'textfield',
      '#size' => 32,
      '#title' => $this->t('Access group label'),
      '#default_value' => $config->get('label', 'Section'),
      '#description' => $this->t('Label shown to define a Workbench Access control group.'),
    );
    $form['labels']['plural_label'] = array(
      '#type' => 'textfield',
      '#size' => 32,
      '#title' => $this->t('Access group label (plural form)'),
      '#default_value' => $config->get('plural_label', 'Sections'),
      '#description' => $this->t('Label shown to define a set of Workbench Access control groups.'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('workbench_access.settings');
    $config->set('label', $form_state->getValue('label'))
      ->set('plural_label', $form_state->getValue('plural_label'));
    $new_scheme = $form_state->getValue('scheme');
    if ($config->get('scheme') !== $new_scheme) {
      $config->set('scheme', $new_scheme);
    }
    $extra = $this->manager->getScheme($new_scheme)->configSubmit($form, $form_state);
    foreach ($extra as $key => $value) {
      $config->set($key, $value);
    }
    $config->save();
    parent::submitForm($form, $form_state);
  }

  /**
   * Custom submit handler for schema changes.
   */
  public function submitActiveScheme(array $form, FormStateInterface $form_state) {
    $scheme = $form_state->getValue('scheme');
    $config = $this->config('workbench_access.settings');
    $config->set('scheme', $scheme)
      ->set('parents', array_filter($form_state->getValue($scheme)));
    $config->save();

    $reset_scheme = $form_state->getValue('reset_scheme');
    if (!empty($reset_scheme)) {
      // Flush access data on scheme change.
      $this->manager->flushRoles();
      $this->manager->flushUsers();
      $this->manager->flushFields();
    }
    drupal_set_message($this->t('Access scheme updated.'));
  }

}
