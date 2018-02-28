<?php

namespace Drupal\workbench_access\Form;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\UserInterface;
use Drupal\workbench_access\RoleSectionStorageInterface;
use Drupal\workbench_access\SectionAssociationStorageInterface;
use Drupal\workbench_access\UserSectionStorageInterface;
use Drupal\workbench_access\WorkbenchAccessManagerInterface;
use Drupal\workbench_access\Entity\AccessSchemeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Builds the workbench_access set switch form.
 *
 * @internal
 */
class AssignUserForm extends FormBase {

  /**
   * The user account being edited.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * Workbnech Access manager.
   *
   * @var \Drupal\workbench_access\WorkbenchAccessManagerInterface
   */
  protected $manager;

  /**
   * The access scheme storage handler.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected $schemeStorage;

  /**
   * The section storage handler.
   *
   * @var \Drupal\workbench_access\SectionAssociationStorageInterface
   */
  protected $sectionStorage;

  /**
   * The user section storage service.
   *
   * @var \Drupal\workbench_access\UserSectionStorageInterface
   */
  protected $userSectionStorage;

  /**
   * The role section storage service.
   *
   * @var \Drupal\workbench_access\RoleSectionStorageInterface
   */
  protected $roleSectionStorage;

  /**
   * Constructs the form object.
   *
   * @param \Drupal\workbench_access\WorkbenchAccessManagerInterface $manager
   *   The workbench access manager.
   * @param \Drupal\Core\Config\Entity\ConfigEntityStorageInterface $scheme_storage
   *   The access scheme storage handler.
   * @param \Drupal\workbench_access\SectionAssociationStorageInterface $section_storage
   *   The section storage handler.
   * @param \Drupal\workbench_access\UserSectionStorageInterface $user_section_storage
   *   The user section storage service.
   * @param \Drupal\workbench_access\RoleSectionStorageInterface $role_section_storage
   *   The role section storage service.
   */
  public function __construct(WorkbenchAccessManagerInterface $manager, ConfigEntityStorageInterface $scheme_storage, SectionAssociationStorageInterface $section_storage, UserSectionStorageInterface $user_section_storage, RoleSectionStorageInterface $role_section_storage) {
    $this->manager = $manager;
    $this->schemeStorage = $scheme_storage;
    $this->sectionStorage = $section_storage;
    $this->userSectionStorage = $user_section_storage;
    $this->roleSectionStorage = $role_section_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.workbench_access.scheme'),
      $container->get('entity.manager')->getStorage('access_scheme'),
      $container->get('entity.manager')->getStorage('section_association'),
      $container->get('workbench_access.user_section_storage'),
      $container->get('workbench_access.role_section_storage')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'workbench_access_assign_user';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, UserInterface $user = NULL) {
    $account = $this->currentUser();
    $this->user = $user;

    // Load all schemes.
    $schemes = $this->schemeStorage->loadMultiple();
    foreach ($schemes as $scheme) {
      $user_sections = $this->userSectionStorage->getUserSections($scheme, $user, FALSE);
      $admin_sections = $this->userSectionStorage->getUserSections($scheme, $account, FALSE);
      $options = $this->getFormOptions($scheme);
      $role_sections = $this->roleSectionStorage->getRoleSections($scheme, $user);
      foreach ($options as $value => $label) {
        if (in_array($value, $role_sections)) {
          $options[$value] = '<strong>' . $options[$value] . ' * </strong>';
        }
      }
      $form[$scheme->id()] = [
        '#type' => 'fieldset',
        '#collapsible' => TRUE,
        '#collapsed' => FALSE,
        '#title' => $scheme->getPluralLabel(),
      ];
      $form[$scheme->id()]['active'] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Assigned sections'),
        '#options' => $options,
        '#default_value' => $user_sections,
        '#description' => $this->t('Sections assigned by role are emphasized with an * but not selected unless they are also assigned directly to the user. They need not be selected. Access granted by role cannot be revoked from this form.'),
      ];
      $no_access = array_diff($user_sections, array_keys($options));
      $form[$scheme->id()]['no_access'] = [
        '#type' => 'value',
        '#value' => $no_access,
      ];
      $form[$scheme->id()]['scheme'] = [
        '#type' => 'value',
        '#value' => $scheme,
      ];
    }
    $form['schemes'] = [
      '#type' => 'value',
      '#value' => array_keys($schemes),
    ];
    $form['actions'] = [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#name' => 'save',
        '#value' => $this->t('Save'),
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $items = [];
    if (count($values['schemes']) < 2) {
      $id = current($values['schemes']);
      $items[$id]['scheme'] = $values['scheme'];
      $items[$id]['selections'] = $values['active'];
      $items[$id]['no_access'] = $values['no_access'];
    }
    else {
      foreach ($values['schemes'] as $id) {
        $items[$id]['scheme'] = $values[$id]['scheme'];
        $items[$id]['selections'] = $values[$id]['active'];
        $items[$id]['no_access'] = $values[$id]['no_access'];
      }
    }
    foreach ($items as $item) {
      $sections = array_filter($item['selections'], function($val) {
        return !empty($val);
      });
      $sections = array_keys($sections);
      $sections = array_merge($sections, $item['no_access']);
      $this->userSectionStorage->addUser($item['scheme'], $this->user, $sections);
      $remove_sections = array_keys(array_filter($item['selections'], function($val) {
        return empty($val);
      }));
      $this->userSectionStorage->removeUser($item['scheme'], $this->user, $remove_sections);
    }
  }

  /**
   * Checks access for the form.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function checkAccess() {
    $permissions = ['assign workbench access', 'assign selected workbench access'];
    return AccessResult::allowedifHasPermissions(\Drupal::currentUser(), $permissions, 'OR');
  }

  /**
   * Gets available form opotions for this user.
   *
   * @param \Drupal\workbench_access\Entity\AccessSchemeInterface $scheme
   *   The access scheme being processed by the form.
   */
  public function getFormOptions(AccessSchemeInterface $scheme) {
    $options = [];
    if ($this->manager->userInAll($scheme)) {
      $list = $this->manager->getAllSections($scheme, FALSE);
    }
    else {
      $list = $this->userSectionStorage->getUserSections($scheme);
    }
    $access_scheme = $scheme->getAccessScheme();
    foreach ($list as $id) {
      if ($section = $access_scheme->load($id)) {
        $options[$id] = str_repeat('-', $section['depth']) . ' ' . $section['label'];
      }
    }
    return $options;
  }

}
