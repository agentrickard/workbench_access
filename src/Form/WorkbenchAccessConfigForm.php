<?php

namespace Drupal\workbench_access\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Workbench access settings for this site.
 */
class WorkbenchAccessConfigForm extends ConfigFormBase {

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
    $form['deny_on_empty'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Deny access to unassigned content'),
      '#default_value' => $config->get('deny_on_empty'),
      '#description' => $this->t('For content under access control, deny access for any content not assigned to a section. This setting is off by default so that installing the module does not break existing site behavior.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('workbench_access.settings');
    $config->set('deny_on_empty', $form_state->getValue('deny_on_empty'))->save();
    parent::submitForm($form, $form_state);
  }

}
