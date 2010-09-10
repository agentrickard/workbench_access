<?php
// $Id$

/**
 * @file
 *   Workbench Access admin file.
 */

/**
 * Test callback for admin.
 */
function workbench_access_settings_form(&$form, &$form_state) {
  $info = module_invoke_all('workbench_access_info');
  $options = array();
  foreach ($info as $key => $value) {
    $options[$key] = $value['name'] . '<br /><em>' . $value['description'] . '</em>';
  }
  $active = variable_get('workbench_access', 'workbench_access');
  $form = array();
  $form['workbench_access'] = array(
    '#type' => 'radios',
    '#title' => t('Active access scheme'),
    '#options' => $options,
    '#default_value' => $active,
    '#description' => t('Select the access scheme to use for the site.'),
    '#required' => TRUE,
  );
  // Get the configuration options for the active schemes.
  foreach ($info as $key => $value) {
    if (isset($info[$key]['configuration']) && function_exists($info[$key]['configuration'])) {
      $func = $info[$key]['configuration'];
      $func($form, $form_state);
      foreach (array('validate', 'submit') as $action) {
        if (function_exists($func . '_' . $action)) {
          $form['#' . $action][] = $func . '_' . $action;
        }
      }
    }
  }
  /*
  $options = array(
    t('Always assert response'),
    t('Always assert on TRUE'),
    t('Always assert on FALSE'),
  );
  $form['workbench_access_behavior'] = array(
    '#type' => 'radios',
    '#title' => t('Node access integration'),
    '#options' => $options,
    '#default_value' => variable_get('workbench_access_behavior', 0),
    '#description' => t('Define how to intract with node access declarations.'),
  );*/
  return system_settings_form($form);
}

/**
 * Administer section definitions.
 */
function workbench_access_section_form(&$form, &$form_state) {
  $scheme = variable_get('workbench_access', 'workbench_access');
  $func = $scheme . '_workbench_access_info';
  $info = $func();
  $data = $info[$scheme];
  $active = workbench_access_get_ids_by_scheme($data);
  $tree = workbench_access_tree($data);
  workbench_build_tree($tree);
  $form = array();
  $form['access_scheme'] = array(
    '#type' => 'value',
    '#value' => $data,
  );
  $form['sections']['#tree'] = TRUE;
  $active_items = array_keys($active);
  $parent = 0;
  $used = array();
  foreach ($tree as $section) {
    if (in_array($section['access_id'], $used)) {
      continue;
    }
    if ($section['depth'] == 0) {
      $parent = $section['access_id'];
      $collapsed = TRUE;
      if (isset($active[$section['access_id']])) {
      
        $collapsed = FALSE;
      }
      elseif (!empty($section['children'])) {
        $check = array_intersect($active_items, $section['children']);
        if (!empty($check)) {
          $collapsed = FALSE;
        }
      }
      $form['sections'][$parent] = array(
        '#type' => 'fieldset',
        '#title' => check_plain($section['name']),
        '#collapsible' => TRUE,
        '#collapsed' => $collapsed,
        '#tree' => TRUE,
      );
    }
    $form['sections'][$parent][$section['access_id']] = array(
      '#type' => 'checkbox',
      '#title' => ($section['depth'] == 0 ? t('All of') . ' ' : '') . str_repeat('-', $section['depth']) . ' ' . check_plain($section['name']),
      '#default_value' => isset($active[$section['access_id']]),
    );
    $used[] = $section['access_id'];
  }
  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save sections'),
  );
  return $form;
}

/**
 * Save the active section definitions.
 */
function workbench_access_section_form_submit(&$form, &$form_state) {
  $access_scheme = $form_state['values']['access_scheme'];
  $results = $form_state['values']['sections'];
  $sections = array();
  foreach ($results as $result) {
    $sections = array_merge($sections, array_keys(array_filter($result)));
  }
  workbench_access_rebuild_scheme($access_scheme, $sections);
  if (empty($sections)) {
    drupal_set_message(t('There are now no editorial sections for your site.'), 'warning');
  }
  else {
    drupal_set_message(t('Editorial sections saved.'));
  }
}

/**
 * Assign sections to a user.
 */
function workbench_access_user_form(&$form, &$form_state, $account) {
  if (!isset($account->workbench_access)) {
    $account = user_load($account->uid);
  }
  $scheme = variable_get('workbench_access', 'workbench_access');
  $func = $scheme . '_workbench_access_info';
  $info = $func();
  $data = $info[$scheme];
  $active = workbench_access_get_ids_by_scheme($data);
  $tree = workbench_access_tree($data);
  workbench_build_tree($tree);
  $form = array();
  $form['uid'] = array(
    '#type' => 'value',
    '#value' => $account->uid,
  );
  $form['access_scheme'] = array(
    '#type' => 'value',
    '#value' => $data,
  );
  $sections = $account->workbench_access;
  $form['user_sections'] = array(
    '#type' => 'value',
    '#value' => array_keys($sections),
  );
  $form['sections']['#tree'] = TRUE;
  $active_items = array_keys($active);
  $section_items = array_keys($sections);
  $parent = 0;
  $used = array();
  foreach ($tree as $section) {
    if (in_array($section['access_id'], $used)) {
      continue;
    }
    if ($section['depth'] == 0) {
      $display = FALSE;
      $parent = $section['access_id'];
      $collapsed = TRUE;
      if (!empty($sections[$section['access_id']])) {
        $collapsed = FALSE;
      }
      elseif (!empty($section['children'])) {
        $check = array_intersect($section_items, $section['children']);
        if (!empty($check)) {
          $collapsed = FALSE;
        }
      }
      if (!empty($active[$section['access_id']])) {
        $display = TRUE;
      }
      elseif (!empty($section['children'])) {
        $check = array_intersect($active_items, $section['children']);
        if (!empty($check)) {
          $display = TRUE;
        }
      }
      if ($display) {
        $form['sections'][$parent] = array(
          '#type' => 'fieldset',
          '#title' => check_plain($section['name']),
          '#collapsible' => TRUE,
          '#collapsed' => $collapsed,
          '#tree' => TRUE,
        );
      }
    }
    if (isset($active[$section['access_id']])) {
      $form['sections'][$parent][$section['access_id']] = array(
        '#type' => 'checkbox',
        '#title' => ($section['depth'] == 0 ? t('All of') . ' ' : '') . str_repeat('-', $section['depth']) . ' ' . check_plain($section['name']),
        '#default_value' => isset($sections[$section['access_id']]),
      );
    }
    else {
      $form['sections'][$parent][$section['access_id']] = array(
        '#type' => 'value',
        '#value' => 0,
      );
    }
    $used[] = $section['access_id'];
  }
  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save sections'),
  );
  return $form;
}

/**
 * Save user assignments.
 */
function workbench_access_user_form_submit($form, &$form_state) {
  $access_scheme = $form_state['values']['access_scheme'];
  $user_sections = $form_state['values']['user_sections'];
  $results = $form_state['values']['sections'];
  $sections = array();
  foreach ($results as $result) {
    $sections = array_merge($sections, array_keys(array_filter($result)));
  }
  if (empty($sections)) {
    drupal_set_message(t('There are now no editorial sections for this user.'), 'warning');
  }
  $uid = $form_state['values']['uid'];
  workbench_access_rebuild_user($uid, $access_scheme['access_scheme'], $sections, $user_sections);
  if (!empty($sections)) {
    drupal_set_message(t('User permissions updated.'));
  }
}
