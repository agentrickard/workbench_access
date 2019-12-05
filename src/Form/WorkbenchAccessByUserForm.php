<?php

namespace Drupal\workbench_access\Form;

use Drupal\workbench_access\Entity\AccessSchemeInterface;
use Drupal\workbench_access\UserSectionStorageInterface;
use Drupal\workbench_access\WorkbenchAccessManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\user\Entity\User;

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
  public function buildForm(array $form, FormStateInterface $form_state, AccessSchemeInterface $access_scheme = NULL, $id = NULL) {
    $this->scheme = $access_scheme;

    $element = $access_scheme->getAccessScheme()->load($id);
    $existing_editors = $this->userSectionStorage->getEditors($access_scheme, $id);
    $potential_editors = $this->userSectionStorage->getPotentialEditors($id);

    $form['existing_editors'] = ['#type' => 'value', '#value' => $existing_editors];
    $form['section_id'] = ['#type' => 'value', '#value' => $id];

    $form['add'] = [
      '#type' => 'container',
    ];
    if ($potential_editors) {
      $toggle = '<br>' . $this->t('<a class="switch" href="#">Switch between textarea/autocomplete</a>');
      $form['add']['editors_add'] = [
        '#type' => 'entity_autocomplete',
        '#target_type' => 'user',
        '#selection_handler' => 'workbench_access:user:' . $access_scheme->id(),
        '#selection_settings' => [
          'include_anonymous' => FALSE,
          'match_operator' => 'STARTS_WITH',
          'filter' => ['section_id' => $id],
        ],
        '#title' => $this->t('Add editors to the %label section.', ['%label' => $element['label']]),
        '#description' => $this->t('Search editors to add to this section, separate with comma to add multiple editors.<br>Only users in roles with permission to be assigned can be referenced.') . $toggle,
        '#tags' => TRUE,
      ];
      // The authenticated user role is not stored in the database, so we cannot
      // query for it. If 'authenticated user' is present, do not filter on
      // roles at all.
      $potential_editors_roles = $this->userSectionStorage->getPotentialEditorsRoles($id);
      if (!isset($potential_editors_roles[AccountInterface::AUTHENTICATED_ROLE])) {
        // Add the role filter, which uses the role id stored as array_keys().
        $form['add']['editors_add']['#selection_settings']['filter'] = ['role' => array_keys($potential_editors_roles), 'section_id' => $id];
      }
      $form['add']['editors_add_mass'] = [
        '#type' => 'textarea',
        '#title' => $this->t('Add editors to the %label section.', ['%label' => $element['label']]),
        '#description' => $this->t('Add a list of user ids or usernames separated with comma or new line. Invalid or existing users will be ignored.') . $toggle,
      ];
      $form['add']['actions'] = [
        '#type' => 'actions',
        'submit' => [
          '#type' => 'submit',
          '#name' => 'add',
          '#value' => $this->t('Add'),
        ],
      ];
    }
    else {
      $form['add']['message'] = [
        '#type' => 'markup',
        '#markup' => '<p>' . $this->t('There are no additional users that can be added to the %label section', ['%label' => $element['label']]) . '</p>',
      ];
    }

    $form['remove'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Existing editors in the %label section.', ['%label' => $element['label']]),
      '#description' => $this->t('<p>Current editors list. Use the checkboxes to remove editors from this section.</p>'),
    ];
    // Prepare editors list for tableselect.
    $editors_data = [];

    // Do we neeed to paginate?
    $total = count($existing_editors);
    $limit = 50;
    $existing = array_chunk($existing_editors, $limit, TRUE);
    $pages = count($existing);
    // pager_default_initialize() is deprecated in 8.8.
    if (\Drupal::hasService('pager.manager')) {
      $pager_manager = \Drupal::service('pager.manager');
      $pager = $pager_manager->createPager($total, $limit);
      $page = $pager->getCurrentPage();
    }
    else {
      $page = pager_default_initialize($total, $limit);
    }
    $start = $page * $limit;
    if ($pages > 1) {
      $existing_editors = $existing[$page];
      $form['remove']['count'] = [
        '#type' => 'markup',
        '#markup' => $this->t('<p>Page @x of @pages. Showing editors @start - @end of @count total.</p>', [
          '@x' => $page + 1,
          '@pages' => $pages,
          '@start' => $start + 1,
          '@end' => $start + count($existing_editors),
          '@count' => $total,
        ]),
      ];
      $form['remove']['pagination'] = [
        '#type' => 'pager',
        '#weight' => 2,
      ];
    }
    if ($existing_editors) {
      foreach ($existing_editors as $uid => $username) {
        $editors_data[$uid] = [$username];
      }
      asort($editors_data);
    }
    $form['remove']['editors_remove'] = [
      '#type' => 'tableselect',
      '#header' => [$this->t('Username')],
      '#options' => $editors_data,
      '#empty' => $this->t('There are no editors assigned to the %label section.', ['%label' => $element['label']]),
    ];
    if ($existing_editors) {
      $form['remove']['actions'] = [
        '#type' => 'actions',
        'submit' => [
          '#type' => 'submit',
          '#name' => 'remove',
          '#value' => $this->t('Remove'),
        ],
      ];
    }
    $form['#attached']['library'][] = 'workbench_access/admin';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    $existing_editors = $form_state->getValue('existing_editors');
    $section_id = $form_state->getValue('section_id');

    // Add new editors.
    if ($trigger['#name'] == 'add') {
      $uids_added = [];
      if ($add_editors = $form_state->getValue('editors_add')) {
        foreach ($add_editors as $target_entity) {
          $user_id = $target_entity['target_id'];
          if (!isset($existing_editors[$user_id])) {
            $uids_added[] = $user_id;
          }
        }
      }
      elseif ($add_editors = $form_state->getValue('editors_add_mass')) {
        $add_editors = preg_split('/[\ \n\,]+/', $add_editors);
        foreach ($add_editors as $uid_or_username) {
          // This is a uid.
          if ((int) $uid_or_username > 0) {
            if (!isset($existing_editors[(int) $uid_or_username])) {
              $uids_added[] = $uid_or_username;
            }
          }
          elseif (strlen($uid_or_username) > 1) {
            $user = user_load_by_name(trim($uid_or_username));
            if ($user) {
              if (!isset($existing_editors[$user->id()])) {
                $uids_added[] = $user->id();
              }
            }
          }
        }
      }
      if (count($uids_added)) {
        $this->addEditors($uids_added, $section_id, $existing_editors);
      }
      else {
        \Drupal::messenger()->addMessage($this->t('No valid users were selected to add'), 'warning');
      }
    }

    // Remove unwanted editors.
    if ($trigger['#name'] == 'remove') {
      if ($remove_editors = array_filter($form_state->getValue('editors_remove'))) {
        $this->removeEditors($remove_editors, $section_id, $existing_editors);
      }
      else {
        \Drupal::messenger()->addMessage($this->t('No users were selected to remove.'), 'warning');
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
    return $this->t('Editors assigned to %label', ['%label' => $element['label']]);
  }

  /**
   * Add editors to the section.
   *
   * @param array $uids
   *   User ids to add.
   * @param int $section_id
   *   Workbench access section id.
   * @param array $existing_editors
   *   Existing editors uids.
   */
  protected function addEditors(array $uids, $section_id, array $existing_editors = []) {
    /** @var \Drupal\user\UserInterface[] $users */
    $users = User::loadMultiple($uids);
    $editors_added = [];
    foreach ($users as $uid => $user) {
      // Add user to section.
      if (!isset($existing_editors[$uid])) {
        $this->userSectionStorage->addUser($this->scheme, $user, [$section_id]);
        $editors_added[] = $user->getDisplayName();
      }
    }
    if (count($editors_added)) {
      $text = $this->formatPlural(count($editors_added),
        'User @user added.',
        'Users added: @user',
        ['@user' => implode(', ', $editors_added)]
      );
      \Drupal::messenger()->addMessage($text);
    }
  }

  /**
   * Remove editors to the section.
   *
   * @param array $uids
   *   User ids to add.
   * @param int $section_id
   *   Workbench access section id.
   * @param array $existing_editors
   *   Existing editors uids.
   */
  protected function removeEditors(array $uids, $section_id, array $existing_editors = []) {
    $editors_removed = [];
    /** @var \Drupal\user\UserInterface[] $users */
    $users = User::loadMultiple($uids);
    foreach ($users as $user_id => $user) {
      if (isset($existing_editors[$user_id])) {
        $this->userSectionStorage->removeUser($this->scheme, $user, [$section_id]);
        $editors_removed[] = $existing_editors[$user_id];
      }
    }
    if (count($editors_removed)) {
      $text = $this->formatPlural(count($editors_removed),
        'User @user removed.',
        'Users removed: @user',
        ['@user' => implode(', ', $editors_removed)]
      );
      \Drupal::messenger()->addMessage($text);
    }
  }

}
