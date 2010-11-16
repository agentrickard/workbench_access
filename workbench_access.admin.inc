<?php

/**
 * @file
 *   Workbench Access admin file.
 *
 * @copyright (C) Copyright 2010 Palantir.net
 */

/**
 * Test callback for admin.
 */
function workbench_access_settings_form(&$form, &$form_state) {
  // Make sure all hook_invocations exist.
  workbench_access_load_include();
  $info = module_invoke_all('workbench_access_info');
  $options = array();
  foreach ($info as $key => $value) {
    $options[$key] = $value['name'] . '<br /><em>' . $value['description'] . '</em>';
  }
  $active = variable_get('workbench_access', 'taxonomy');
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
  $active = workbench_access_get_active_tree();
  $form = array();
  $form['access_scheme'] = array(
    '#type' => 'value',
    '#value' => $active['access_scheme'],
  );
  $form['sections']['#tree'] = TRUE;
  $active_items = array_keys($active['active']);
  $parent = 0;
  $used = array();
  foreach ($active['tree'] as $section) {
    if (in_array($section['access_id'], $used)) {
      continue;
    }
    if ($section['depth'] == 0) {
      $parent = $section['access_id'];
      $collapsed = TRUE;
      if (isset($active['active'][$parent])) {
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
      '#default_value' => isset($active['active'][$section['access_id']]),
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
 *
 * @param $account
 *   The user account being acted upon.
 */
function workbench_access_user_form(&$form, &$form_state, $account) {
  if (!isset($account->workbench_access)) {
    $account = user_load($account->uid);
  }
  $active = workbench_access_get_active_tree();
  $form = array();
  $form['uid'] = array(
    '#type' => 'value',
    '#value' => $account->uid,
  );
  $form['access_scheme'] = array(
    '#type' => 'value',
    '#value' => $active['access_scheme'],
  );
  $form['helptext'] = array(
    '#type' => 'markup',
    '#markup' => t('The list below shows your editorial assignments. Active assignments <strong>appear with emphasis</strong>.'),
    '#access' => !user_access('assign_workbench_access'),
  );
  $sections = $account->workbench_access;
  $form['user_sections'] = array(
    '#type' => 'value',
    '#value' => array_keys($sections),
  );
  $form['sections']['#tree'] = TRUE;
  $active_items = array_keys($active['active']);
  $section_items = array_keys($sections);
  $parent = 0;
  $used = array();
  foreach ($active['tree'] as $section) {
    if (in_array($section['access_id'], $used)) {
      continue;
    }
    if ($section['depth'] == 0) {
      $display = FALSE;
      $parent = $section['access_id'];
      $collapsed = TRUE;
      if (!empty($sections[$section['access_id']])) {
        $collapsed = FALSE;
        $display = TRUE;
      }
      elseif (!empty($section['children'])) {
        $check = array_intersect($section_items, $section['children']);
        if (!empty($check)) {
          $collapsed = FALSE;
        }
      }
      // Do we show inactive sections here?
      if (!empty($active['active'][$section['access_id']])) {
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
    // Do we show inactive sections here?
    if (isset($active['active'][$section['access_id']])) {
      if (user_access('assign workbench access') && !empty($active['active'][$section['access_id']])) {
        $form['sections'][$parent][$section['access_id']] = array(
          '#type' => 'checkbox',
          '#title' => ($section['depth'] == 0 ? t('All of') . ' ' : '') . str_repeat('-', $section['depth']) . ' ' . check_plain($section['name']),
          '#default_value' => isset($sections[$section['access_id']]),
          # '#disabled' => empty($sections[$section['access_id']]),
        );
      }
      else {
        $form['sections'][$parent][$section['access_id']] = array(
          '#type' => 'markup',
          '#markup' => ($section['depth'] == 0 ? t('All of') . ' ' : '') . str_repeat('-', $section['depth']) . ' ' . check_plain($section['name']) . '<br />',
        );
        if (isset($sections[$section['access_id']])) {
          // &form_build_id=form-gTz2A0tW_-i8iUnvPLCnytNHmZKp2-0IoL_4JSzjJSI&form_token=yASSDKlt8cnNTaEB4UyynilWp2KZnZQ_2rOLThYhtTQ&form_id=views_exposed_form
          $query = array(
            'access_id[]' => $section['access_id'],
          );
          $form['sections'][$parent][$section['access_id']]['#prefix'] = '<strong>';
          $form['sections'][$parent][$section['access_id']]['#suffix'] = '</strong>'; // .l(t('View content'), 'http://barnard.rickard.vash.palantir.net/admin/config/workbench/page/content', array($query));
        }
      }
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
    '#access' => user_access('assign workbench access'),
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

/**
 * Show user sections.
 */
function workbench_access_sections() {
  global $user;
  $account = $user;
  if (!isset($account->workbench_access)) {
    workbench_access_load_user_data($account);
  }
  return drupal_get_form('workbench_access_user_form', $account);
}

/**
 * Display the editors for a section.
 *
 * If no group specified, then show a list of active groups, otherwise, load
 * the proper form to edit the members of the group.
 *
 * @param $access_type
 *   The type of access requested (e.g.g taxonomy).
 * @param $access_type_id
 *   The id for this specific access (here, a taxnomy term tid).
 *
 * @return
 *   An editing form or a list of section editors.
 *
 * @see workbench_access_editor_form()
 */
function workbench_access_editors($access_type = NULL, $access_type_id = NULL) {
  if (is_null($access_type) || is_null($access_type_id)) {
    $active = workbench_access_get_active_tree();
    $active['access_scheme']['access_id'] = $access_type_id;
    $active_keys = array_keys($active['active']);
    $output = '<h2>' . t('Editorial sections') . '</h2>';
    $output .= '<p>' . t('The following sections are currently active.');
    if (user_access('administer workbench access')) {
      $output .= ' ' . t('You may <a href="!url">add or delete sections</a>.', array('!url' => url('admin/config/workbench/access'))) .'</p>';
    }
    $list = array();
    foreach ($active['tree'] as $access_id => $section) {
      if (!isset($active['active'][$access_id])) {
        continue;
      }
      // Nest the children so the user understands the hierarchy.
      if ($section['depth'] == 0 || !isset($active['active'][$section['parent']])) {
        $parent = $section['name'];
        $base_depth = $section['depth'];
      }
      $count = db_query("SELECT COUNT(uid) FROM {workbench_access_user} WHERE access_scheme = :access_scheme AND access_id = :access_id",
        array(':access_scheme' => $active['access_scheme']['access_type'], ':access_id' => $access_id)
        )->fetchField();
      $list[$section['access_id']] = str_repeat('-', $section['depth'] - $base_depth) . ' ' . l($section['name'], 'admin/config/workbench/access/editors/' . $active['access_scheme']['access_type'] . '/' . $access_id) . ' [' . format_plural($count, '1 editor', '@count editors') . ']';
    }
    $output .= theme('item_list', array('items' => $list));
    return $output;
  }
  return drupal_get_form('workbench_access_editor_form', $access_type, $access_type_id);
}

/**
 * Generate a user overview form for a section.
 *
 * @param $access_type
 *   The type of access requested (e.g.g taxonomy).
 * @param $access_type_id
 *   The id for this specific access (here, a taxnomy term tid).
 *
 * @return
 *   A form.
 */
function workbench_access_editor_form(&$form, &$form_state, $access_type, $access_type_id) {
  $form = array();
  $active = workbench_access_get_active_tree();
  $active['access_scheme']['access_id'] = $access_type_id;
  if ($active['access_scheme']['access_type'] != $access_type || !isset($active['active'][$access_type_id])) {
    drupal_access_denied();
    drupal_exit();
  }
  $query = db_select('users', 'u')
    ->fields('u', array('uid', 'name'));    
  $query->join('workbench_access_user', 'wau', 'u.uid = wau.uid');
  $query->condition('wau.access_scheme', $access_type)
    ->condition('wau.access_id', $access_type_id)
    ->extend('PagerDefault')->limit(25);
  $result = $query->execute();
  $rows = array();
  $form['users']['#tree'] = TRUE;
  foreach ($result as $account) {
    $form['users'][$account->uid]['name'] = array('#markup' => l($account->name, 'user/'. $account->uid));
    $form['users'][$account->uid]['remove'] = array(
      '#type' => 'checkbox',
      '#title' => t('Remove'),
    );
  }
  $form['add_user'] = array(
    '#type' => 'textfield',
    '#title' => t('Add editor'),
    '#autocomplete_path' => 'workbench_access/autocomplete/' . $access_type . '/' . $access_type_id,
  );
  $form['workbench_access'] = array(
    '#type' => 'value',
    '#value' => $active['access_scheme'],
  );
  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Update editors'),
  );
  return $form;
}

/**
 * Process form submission for adding editors.
 */
function workbench_access_editor_form_submit($form, &$form_state) {
  $values = $form_state['values'];
  if (!empty($values['add_user'])) {
    $account = user_load_by_name($values['add_user']);
    if (!empty($account->status) && !in_array($values['workbench_access']['access_id'], array_keys($account->workbench_access))) {
      workbench_access_user_section_save($account->uid, $values['workbench_access']['access_id'], $values['workbench_access']['access_scheme']);
    }
  }
  if (!empty($values['users'])) {
    foreach ($values['users'] as $key => $value) {
      if (!empty($value['remove'])) {
        workbench_access_user_section_delete($key, $values['workbench_access']['access_id'], $values['workbench_access']['access_scheme']);
      }
    }
  }
}

/**
 * Theme the editor list form.
 */
function theme_workbench_access_editor_form($variables) {
  $form = $variables['form'];
  $scheme = $form['workbench_access']['#value'];
  $access_info = workbench_access_load_access_info($scheme);
  $output = '<h2>' . check_plain($access_info['name']) . '</h2>';
  $output .= '<p>' . t('Active editors for the %section section.', array('%section' => $access_info['name'])) . '</p>';
  $header = array(t('Editor'), t('Actions'));
  $rows = array();
  foreach (element_children($form['users']) as $key) {
    $rows[] = array(
      drupal_render($form['users'][$key]['name']),
      drupal_render($form['users'][$key]['remove']),
    );
  }
  $variables = array(
    'header' => $header,
    'rows' => $rows,
  );
  $output .= theme('table', $variables);
  $output .= drupal_render_children($form);
  return $output;
}
