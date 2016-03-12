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
    if (!$editors) {
      $text = $this->t('There are no editors assigned to the %label section', array('%label' => $element['label']));
      $form['help'] = array(
        '#type' => 'markup',
        '#markup' => $text,
      );
    }
    $potential_editors = $this->manager->getPotentialEditors($id);
kint($potential_editors);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

}
