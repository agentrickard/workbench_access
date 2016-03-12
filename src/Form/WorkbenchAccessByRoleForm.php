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
    $existing_roles = $this->manager->getRoles($id);
    $potential_roles = $this->manager->getPotentialRoles($id);

    $form['existing_roles'] = ['#type' => 'value', '#value' => $existing_roles];
    $form['section_id'] = ['#type' => 'value', '#value' => $id];
    if (!$existing_roles) {
      $text = $this->t('There are no roles assigned to the %label section.', array('%label' => $element['label']));
      $form['help'] = array(
        '#type' => 'markup',
        '#markup' => '<p>' . $text . '</p>',
      );
    }

    if ($potential_roles) {
      $form['roles'] = array(
        '#title' => $this->t('Roles for the %label section.', array('%label' => $element['label'])),
        '#type' => 'checkboxes',
        '#options' => $potential_roles,
        '#default_value' => array_keys($existing_roles),
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
    $roles = $form_state->getValue('roles');
    $existing_roles = $form_state->getValue('existing_roles');
    $id = $form_state->getValue('section_id');
    foreach ($roles as $role_id => $value) {
      // Add user to section.
      if ($value && !isset($existing_roles[$role_id])) {
        $this->manager->addRole($role_id, array($id));
      }
      // Remove user from section.
      if (!$value && isset($existing_roles[$role_id])) {
        $this->manager->removeRole($role_id, array($id));
      }
    }
  }

  public function pageTitle($id) {
    $element = $this->manager->getElement($id);
    return $this->t('Roles assigned to %label', array('%label' => $element['label']));
  }

}
