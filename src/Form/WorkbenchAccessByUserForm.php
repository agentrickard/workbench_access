<?php

/**
 * @file
 * Contains \Drupal\workbench_access\Form\WorkbenchAccessByUserForm.
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
class WorkbenchAccessByUserForm extends FormBase {

  /**
   * The Workbench Access manager service.
   *
   * @var \Drupal\workbench_access\WorkbenchAccessManager
   */
  protected $manager;

  /**
   * Constructs a new WorkbenchAccessConfigForm.
   *
   * @param \Drupal\workbench_access\WorkbenchAccessManagerInterface
   *   The Workbench Access hierarchy manager.
   */
  public function __construct(WorkbenchAccessManagerInterface $manager) {
    $this->manager = $manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.workbench_access.scheme')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'workbench_access_by_user';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $id = NULL) {
    $element = $this->manager->getElement($id);
    $editors = $this->manager->getEditors($id);
    // @TODO: Reset the page title properly.
    $form['title'] = array(
      '#type' => 'markup',
      '#markup' => '<h2>' . $this->t('Assigned editors for %label', array('%label' => $element['label'])) . '</h2>' ,
    );
    $form['existing_editors'] = ['#type' => 'value', '#value' => $editors];
    $form['section_id'] = ['#type' => 'value', '#value' => $id];
    if (!$editors) {
      $text = $this->t('There are no editors assigned to the %label section.', array('%label' => $element['label']));
      $form['help'] = array(
        '#type' => 'markup',
        '#markup' => '<p>' . $text . '</p>',
      );
    }
    $potential_editors = $this->manager->getPotentialEditors($id);
    if ($potential_editors) {
      $form['editors'] = array(
        '#title' => $this->t('Editors for the %label section.', array('%label' => $element['label'])),
        '#type' => 'checkboxes',
        '#options' => $potential_editors,
        '#default_value' => array_keys($editors),
      );
      $form['actions'] = array('#type' => 'actions');
      $form['actions']['submit'] = array('#type' => 'submit', '#value' => $this->t('Submit'));
    }
    else {
      $form['message'] = array(
        '#type' => 'markup',
        '#markup' => '<p>' . $this->t('There are no addtional users that can be added to the %label section', array('%label' => $element['label'])) . '</p>',
      );
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $editors = $form_state->getValue('editors');
    $existing_editors = $form_state->getValue('existing_editors');
    $id = $form_state->getValue('section_id');
    foreach ($editors as $user_id => $value) {
      // Add user to section.
      if ($value && !isset($existing_editors[$user_id])) {
        $this->manager->addUser($user_id, array($id));
      }
      // Remove user from section.
      if (!$value && isset($existing_editors[$user_id])) {
        $this->manager->removeUser($user_id, array($id));
      }
    }
  }

}
