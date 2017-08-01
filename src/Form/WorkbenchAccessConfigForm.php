<?php

namespace Drupal\workbench_access\Form;

use Drupal\workbench_access\WorkbenchAccessManagerInterface;
use Drupal\workbench_access\RoleSectionStorageInterface;
use Drupal\workbench_access\UserSectionStorageInterface;
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
   * User section storage.
   *
   * @var \Drupal\workbench_access\UserSectionStorageInterface
   */
  protected $userSectionStorage;

  /**
   * Role section storage.
   *
   * @var \Drupal\workbench_access\RoleSectionStorageInterface
   */
  protected $roleSectionStorage;

  /**
   * Constructs a new WorkbenchAccessConfigForm.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state keyvalue collection to use.
   * @param \Drupal\workbench_access\WorkbenchAccessManagerInterface
   *   The Workbench Access hierarchy manager.
   * @param \Drupal\workbench_access\UserSectionStorageInterface $user_section_storage
   *   User section storage.
   * @param \Drupal\workbench_access\RoleSectionStorageInterface $role_section_storage
   *   Role section storage.
   */
  public function __construct(ConfigFactoryInterface $config_factory, StateInterface $state, WorkbenchAccessManagerInterface $manager, UserSectionStorageInterface $user_section_storage, RoleSectionStorageInterface $role_section_storage) {
    parent::__construct($config_factory);
    $this->state = $state;
    $this->manager = $manager;
    $this->userSectionStorage = $user_section_storage;
    $this->roleSectionStorage = $role_section_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('state'),
      $container->get('plugin.manager.workbench_access.scheme'),
      $container->get('workbench_access.user_section_storage'),
      $container->get('workbench_access.role_section_storage')
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
      $form['error'] = [
        '#type' => 'item',
        '#title' => $this->t('Error'),
        '#markup' => $this->t('There are no available access schemes to configure.'),
      ];
    }
    else {
      $form['scheme'] = [
        '#type' => 'details',
        '#title' => $this->t('Active access scheme'),
        '#open' => TRUE,
      ];
      $form['scheme']['scheme'] = [
        '#type' => 'radios',
        '#title' => $this->t('Active access scheme'),
        '#options' => $schemes,
        '#default_value' => $config->get('scheme'),
      ];
      $form['scheme']['reset_scheme'] = [
        '#type' => 'checkbox',
        '#default_value' => FALSE,
        '#title' => $this->t('Reset assigned fields, user and role sections'),
        '#states' => [
          'invisible' => [
            ':input[name=scheme]' => ['value' => $config->get('scheme')],
          ],
        ],
        '#description' => $this->t('When switching access schemes, flush current field settings, user and role permissions. Recommended.'),
      ];
      foreach ($schemes as $id => $label) {
        $scheme = $this->manager->getScheme($id);
        $form['scheme']['parents'][$id] = [
          '#type' => 'checkboxes',
          '#title' => $this->t('@label editorial access options', ['@label' => $label]),
          '#options' => $scheme->options(),
          '#default_value' => $config->get('parents', []),
          '#states' => [
            'visible' => [
              ':input[name=scheme]' => ['value' => $id],
            ],
          ],
          '#description' => $this->t('Select the @label options to be used for access control.', ['@label' => $label]),
        ];
      }
      $form['scheme']['set'] = [
        '#type' => 'submit',
        '#value' => $this->t('Set active scheme'),
        '#submit' => ['::submitActiveScheme'],
      ];
    }
    if ($id = $config->get('scheme')) {
      $scheme = $this->manager->getScheme($id);
      $custom = $this->manager->getActiveScheme()->configForm($config->get('parents', []));
    }
    if (!empty($custom)) {
      $form['custom'] = [
        '#type' => 'details',
        '#title' => $this->t('Scheme settings'),
        '#open' => TRUE,
        '#markup' => '<strong>' . $this->t('These settings must be confirmed after saving the scheme and options above.') . '</strong>',
      ];
      $form['custom'] += $custom;
    }
    $form['behavior'] = [
      '#type' => 'details',
      '#title' => $this->t('Advanced behaviors'),
    ];
    $form['behavior']['deny_on_empty'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Deny access to unassigned content'),
      '#default_value' => $config->get('deny_on_empty', 0),
      '#description' => $this->t('For content under access control, deny access for any content not assigned to a section. This setting is off by default so that installing the module does not break existing site behavior.'),
    ];
    $form['behavior']['apply_to_moderation'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Apply to content moderation'),
      '#default_value' => $config->get('apply_to_moderation', 0),
      '#description' => $this->t('Apply access rules to moderation forms for content under access control.'),
    ];
    $form['labels'] = [
      '#type' => 'details',
      '#title' => $this->t('Labels'),
    ];
    $form['labels']['label'] = [
      '#type' => 'textfield',
      '#size' => 32,
      '#title' => $this->t('Access group label'),
      '#default_value' => $config->get('label', 'Section'),
      '#description' => $this->t('Label shown to define a Workbench Access control group.'),
    ];
    $form['labels']['plural_label'] = [
      '#type' => 'textfield',
      '#size' => 32,
      '#title' => $this->t('Access group label (plural form)'),
      '#default_value' => $config->get('plural_label', 'Sections'),
      '#description' => $this->t('Label shown to define a set of Workbench Access control groups.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('workbench_access.settings');
    $config->set('deny_on_empty', $form_state->getValue('deny_on_empty'))
      ->set('apply_to_moderation', $form_state->getValue('apply_to_moderation'))
      ->set('label', $form_state->getValue('label'))
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
      $this->roleSectionStorage->flushRoles();
      $this->userSectionStorage->flushUsers();
      $this->manager->flushFields();
    }
    drupal_set_message($this->t('Access scheme updated.'));
  }

}
