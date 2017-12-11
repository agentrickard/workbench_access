<?php

namespace Drupal\workbench_access\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the access scheme form.
 */
class AccessSchemeForm extends EntityForm {

  /**
   * Access scheme entity.
   *
   * @var \Drupal\workbench_access\Entity\AccessSchemeInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $access_scheme = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $access_scheme->label(),
      '#description' => $this->t("Label for the Access scheme."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $access_scheme->id(),
      '#machine_name' => [
        'exists' => '\Drupal\workbench_access\Entity\AccessScheme::load',
      ],
      '#disabled' => !$access_scheme->isNew(),
    ];

    $form['plural_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Plural Label'),
      '#maxlength' => 255,
      '#default_value' => $access_scheme->getPluralLabel(),
      '#description' => $this->t("Plural Label for the Access scheme."),
      '#required' => TRUE,
    ];

    // @todo add form for selecting plugin type
    // @todo add configuration form to plugins for selecting entity types and
    //  bundles
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $access_scheme = $this->entity;
    $status = $access_scheme->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Access scheme.', [
          '%label' => $access_scheme->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Access scheme.', [
          '%label' => $access_scheme->label(),
        ]));
    }
    $form_state->setRedirectUrl($access_scheme->toUrl('collection'));
  }

}
