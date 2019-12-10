<?php

namespace Drupal\workbench_access\Form;

use Drupal\workbench_access\Entity\AccessSchemeInterface;
use Drupal\workbench_access\RoleSectionStorageInterface;
use Drupal\workbench_access\WorkbenchAccessManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Workbench Access per role.
 */
class WorkbenchAccessByRoleForm extends FormBase {

  /**
   * The Workbench Access manager service.
   *
   * @var \Drupal\workbench_access\WorkbenchAccessManager
   */
  protected $manager;

  /**
   * The role section storage service.
   *
   * @var \Drupal\workbench_access\RoleSectionStorageInterface
   */
  protected $roleSectionStorage;

  /**
   * Scheme.
   *
   * @var \Drupal\workbench_access\Entity\AccessSchemeInterface
   */
  protected $scheme;

  /**
   * Constructs a new WorkbenchAccessConfigForm.
   *
   * @param \Drupal\workbench_access\WorkbenchAccessManagerInterface $manager
   *   The Workbench Access hierarchy manager.
   * @param \Drupal\workbench_access\RoleSectionStorageInterface $role_section_storage
   *   The role section storage service.
   */
  public function __construct(WorkbenchAccessManagerInterface $manager, RoleSectionStorageInterface $role_section_storage) {
    $this->manager = $manager;
    $this->roleSectionStorage = $role_section_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.workbench_access.scheme'),
      $container->get('workbench_access.role_section_storage')
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
  public function buildForm(array $form, FormStateInterface $form_state, AccessSchemeInterface $access_scheme = NULL, $id = NULL) {
    $this->scheme = $access_scheme;
    $element = $access_scheme->getAccessScheme()->load($id);
    $existing_roles = $this->roleSectionStorage->getRoles($access_scheme, $id);
    $potential_roles = $this->roleSectionStorage->getPotentialRolesFiltered($id);

    $form['existing_roles'] = ['#type' => 'value', '#value' => $existing_roles];
    $form['section_id'] = ['#type' => 'value', '#value' => $id];
    if (!$existing_roles) {
      $text = $this->t('There are no roles assigned to the %label section.', ['%label' => $element['label']]);
      $form['help'] = [
        '#type' => 'markup',
        '#markup' => '<p>' . $text . '</p>',
      ];
    }
    if ($potential_roles) {
      $form['roles'] = [
        '#title' => $this->t('Roles for the %label section.', ['%label' => $element['label']]),
        '#type' => 'checkboxes',
        '#options' => $potential_roles,
        '#default_value' => $existing_roles,
      ];
      $form['actions'] = ['#type' => 'actions'];
      $form['actions']['submit'] = ['#type' => 'submit', '#value' => $this->t('Submit')];
    }
    if (count($potential_roles) == count($existing_roles)) {
      $form['message'] = [
        '#type' => 'markup',
        '#markup' => '<p>' . $this->t('There are no additional roles that can be added to the %label section', ['%label' => $element['label']]) . '</p>',
      ];
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
      // Add role to section.
      if ($value === $role_id) {
        $this->roleSectionStorage->addRole($this->scheme, $role_id, [$id]);
      }
      // Remove role from section.
      else {
        $this->roleSectionStorage->removeRole($this->scheme, $role_id, [$id]);
      }
    }
  }

  /**
   * Returns a dynamic page title for the route.
   *
   * @param \Drupal\workbench_access\Entity\AccessSchemeInterface $access_scheme
   *   Access scheme.
   * @param string $id
   *   The section id.
   *
   * @return string
   *   A page title.
   */
  public function pageTitle(AccessSchemeInterface $access_scheme, $id) {
    $element = $access_scheme->getAccessScheme()->load($id);
    return $this->t('Roles assigned to %label', ['%label' => $element['label']]);
  }

}
