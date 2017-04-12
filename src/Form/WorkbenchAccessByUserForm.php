<?php

namespace Drupal\workbench_access\Form;

use Drupal\workbench_access\UserSectionStorageInterface;
use Drupal\workbench_access\WorkbenchAccessManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Workbench Access per user.
 */
class WorkbenchAccessByUserForm extends FormBase {

  /**
   * The Workbench Access manager service.
   *
   * @var \Drupal\workbench_access\WorkbenchAccessManager
   */
  protected $manager;

  /**
   * The user section storage service.
   *
   * @var \Drupal\workbench_access\UserSectionStorageInterface
   */
  protected $userSectionStorage;

  /**
   * Constructs a new WorkbenchAccessConfigForm.
   *
   * @param \Drupal\workbench_access\WorkbenchAccessManagerInterface $manager
   *   The Workbench Access hierarchy manager.
   * @param \Drupal\workbench_access\UserSectionStorageInterface $user_section_storage
   *   The user section storage service.
   */
  public function __construct(WorkbenchAccessManagerInterface $manager, UserSectionStorageInterface $user_section_storage) {
    $this->manager = $manager;
    $this->userSectionStorage = $user_section_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.workbench_access.scheme'),
      $container->get('workbench_access.user_section_storage')
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
    $existing_editors = $this->userSectionStorage->getEditors($id);
    $potential_editors = $this->userSectionStorage->getPotentialEditors($id);

    $form['existing_editors'] = ['#type' => 'value', '#value' => $existing_editors];
    $form['section_id'] = ['#type' => 'value', '#value' => $id];
    if (!$existing_editors) {
      $text = $this->t('There are no editors assigned to the %label section.', array('%label' => $element['label']));
      $form['help'] = array(
        '#type' => 'markup',
        '#markup' => '<p>' . $text . '</p>',
      );
    }

    if ($potential_editors) {
      $form['editors'] = array(
        '#title' => $this->t('Editors for the %label section.', array('%label' => $element['label'])),
        '#type' => 'checkboxes',
        '#options' => $potential_editors,
        '#default_value' => array_keys($existing_editors),
      );
      $form['actions'] = array('#type' => 'actions');
      $form['actions']['submit'] = array('#type' => 'submit', '#value' => $this->t('Submit'));
    }
    else {
      $form['message'] = array(
        '#type' => 'markup',
        '#markup' => '<p>' . $this->t('There are no additional users that can be added to the %label section', array('%label' => $element['label'])) . '</p>',
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
        $this->userSectionStorage->addUser($user_id, array($id));
      }
      // Remove user from section.
      if (!$value && isset($existing_editors[$user_id])) {
        $this->userSectionStorage->removeUser($user_id, array($id));
      }
    }
  }

  /**
   * Returns a dynamic page title for the route.
   *
   * @param string $id
   *   The section id.
   *
   * @return string
   *   A page title.
   */
  public function pageTitle($id) {
    $element = $this->manager->getElement($id);
    return $this->t('Editors assigned to %label', array('%label' => $element['label']));
  }

}
