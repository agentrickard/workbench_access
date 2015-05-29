<?php

/**
 * @file
 * Contains \Drupal\workbench_access\Form\WorkbenchAccessConfigForm.
 */

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

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /*
    $this->config('workbench_access.settings')
      ->set('cache.page.max_age', $form_state->getValue('page_cache_maximum_age'))
      ->set('css.preprocess', $form_state->getValue('preprocess_css'))
      ->set('js.preprocess', $form_state->getValue('preprocess_js'))
      ->save();
    */
    parent::submitForm($form, $form_state);
  }


}
