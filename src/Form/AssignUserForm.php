<?php

namespace Drupal\workbench_access\Form;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\UserInterface;
use Drupal\workbench_access\SectionAssociationStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Builds the workbench_access set switch form.
 *
 * @internal
 */
class AssignUserForm extends FormBase {

  /**
   * The account the workbench_access set is for.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * The workbench_access set storage.
   *
   * @var \Drupal\workbench_access\SectionAssociationStorageInterface
   */
  protected $sectionStorage;

  /**
   * Constructs a SwitchShortcutSet object.
   *
   * @param \Drupal\workbench_access\SectionAssociationStorageInterface $storage
   *   The shortcut set storage.
   */
  public function __construct(SectionAssociationStorageInterface $storage) {
    $this->sectionStorage = $storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')->getStorage('section_association')
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

    $form['test'] = ['#markup' => 'test'];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $account = $this->currentUser();

    $account_is_user = $this->user->id() == $account->id();
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

}
