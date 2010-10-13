<?php
$view = new view;
$view->name = 'workbench_access_content';
$view->description = 'Workbench content';
$view->tag = 'Workbench';
$view->view_php = '';
$view->base_table = 'node';
$view->is_cacheable = FALSE;
$view->api_version = '3.0-alpha1';
$view->disabled = FALSE; /* Edit this to true to make a default view disabled initially */

/* Display: Defaults */
$handler = $view->new_display('default', 'Defaults', 'default');
$handler->display->display_options['use_ajax'] = TRUE;
$handler->display->display_options['access']['type'] = 'perm';
$handler->display->display_options['access']['perm'] = 'access workbench';
$handler->display->display_options['cache']['type'] = 'none';
$handler->display->display_options['query']['type'] = 'views_query';
$handler->display->display_options['exposed_form']['type'] = 'basic';
$handler->display->display_options['pager']['type'] = 'full';
$handler->display->display_options['pager']['options']['items_per_page'] = '50';
$handler->display->display_options['pager']['options']['offset'] = '0';
$handler->display->display_options['pager']['options']['id'] = '0';
$handler->display->display_options['style_plugin'] = 'table';
$handler->display->display_options['style_options']['columns'] = array(
  'title' => 'title',
  'section' => 'section',
  'type' => 'type',
  'status' => 'status',
  'changed' => 'changed',
  'edit' => 'edit',
);
$handler->display->display_options['style_options']['default'] = 'changed';
$handler->display->display_options['style_options']['info'] = array(
  'title' => array(
    'sortable' => 1,
    'align' => '',
    'separator' => '',
  ),
  'section' => array(
    'sortable' => 1,
    'align' => '',
    'separator' => '',
  ),
  'type' => array(
    'sortable' => 1,
    'align' => '',
    'separator' => '',
  ),
  'status' => array(
    'sortable' => 1,
    'align' => '',
    'separator' => '',
  ),
  'changed' => array(
    'sortable' => 1,
    'align' => '',
    'separator' => '',
  ),
  'edit' => array(
    'align' => '',
    'separator' => '',
  ),
);
$handler->display->display_options['style_options']['override'] = 1;
$handler->display->display_options['style_options']['sticky'] = 0;
$handler->display->display_options['style_options']['order'] = 'desc';
/* Field: Node: Title */
$handler->display->display_options['fields']['title']['id'] = 'title';
$handler->display->display_options['fields']['title']['table'] = 'node';
$handler->display->display_options['fields']['title']['field'] = 'title';
$handler->display->display_options['fields']['title']['alter']['alter_text'] = 0;
$handler->display->display_options['fields']['title']['alter']['make_link'] = 0;
$handler->display->display_options['fields']['title']['alter']['absolute'] = 0;
$handler->display->display_options['fields']['title']['alter']['trim'] = 0;
$handler->display->display_options['fields']['title']['alter']['word_boundary'] = 1;
$handler->display->display_options['fields']['title']['alter']['ellipsis'] = 1;
$handler->display->display_options['fields']['title']['alter']['strip_tags'] = 0;
$handler->display->display_options['fields']['title']['alter']['html'] = 0;
$handler->display->display_options['fields']['title']['hide_empty'] = 0;
$handler->display->display_options['fields']['title']['empty_zero'] = 0;
$handler->display->display_options['fields']['title']['link_to_node'] = 1;
/* Field: Workbench Access: Section */
$handler->display->display_options['fields']['section']['id'] = 'section';
$handler->display->display_options['fields']['section']['table'] = 'workbench_access';
$handler->display->display_options['fields']['section']['field'] = 'section';
$handler->display->display_options['fields']['section']['alter']['alter_text'] = 0;
$handler->display->display_options['fields']['section']['alter']['make_link'] = 0;
$handler->display->display_options['fields']['section']['alter']['absolute'] = 0;
$handler->display->display_options['fields']['section']['alter']['trim'] = 0;
$handler->display->display_options['fields']['section']['alter']['word_boundary'] = 1;
$handler->display->display_options['fields']['section']['alter']['ellipsis'] = 1;
$handler->display->display_options['fields']['section']['alter']['strip_tags'] = 0;
$handler->display->display_options['fields']['section']['alter']['html'] = 0;
$handler->display->display_options['fields']['section']['hide_empty'] = 0;
$handler->display->display_options['fields']['section']['empty_zero'] = 0;
/* Field: Node: Type */
$handler->display->display_options['fields']['type']['id'] = 'type';
$handler->display->display_options['fields']['type']['table'] = 'node';
$handler->display->display_options['fields']['type']['field'] = 'type';
$handler->display->display_options['fields']['type']['alter']['alter_text'] = 0;
$handler->display->display_options['fields']['type']['alter']['make_link'] = 0;
$handler->display->display_options['fields']['type']['alter']['absolute'] = 0;
$handler->display->display_options['fields']['type']['alter']['trim'] = 0;
$handler->display->display_options['fields']['type']['alter']['word_boundary'] = 1;
$handler->display->display_options['fields']['type']['alter']['ellipsis'] = 1;
$handler->display->display_options['fields']['type']['alter']['strip_tags'] = 0;
$handler->display->display_options['fields']['type']['alter']['html'] = 0;
$handler->display->display_options['fields']['type']['hide_empty'] = 0;
$handler->display->display_options['fields']['type']['empty_zero'] = 0;
$handler->display->display_options['fields']['type']['link_to_node'] = 0;
$handler->display->display_options['fields']['type']['machine_name'] = 0;
/* Field: Node: Published */
$handler->display->display_options['fields']['status']['id'] = 'status';
$handler->display->display_options['fields']['status']['table'] = 'node';
$handler->display->display_options['fields']['status']['field'] = 'status';
$handler->display->display_options['fields']['status']['alter']['alter_text'] = 0;
$handler->display->display_options['fields']['status']['alter']['make_link'] = 0;
$handler->display->display_options['fields']['status']['alter']['absolute'] = 0;
$handler->display->display_options['fields']['status']['alter']['trim'] = 0;
$handler->display->display_options['fields']['status']['alter']['word_boundary'] = 1;
$handler->display->display_options['fields']['status']['alter']['ellipsis'] = 1;
$handler->display->display_options['fields']['status']['alter']['strip_tags'] = 0;
$handler->display->display_options['fields']['status']['alter']['html'] = 0;
$handler->display->display_options['fields']['status']['hide_empty'] = 0;
$handler->display->display_options['fields']['status']['empty_zero'] = 0;
$handler->display->display_options['fields']['status']['not'] = 0;
/* Field: Node: Updated date */
$handler->display->display_options['fields']['changed']['id'] = 'changed';
$handler->display->display_options['fields']['changed']['table'] = 'node';
$handler->display->display_options['fields']['changed']['field'] = 'changed';
$handler->display->display_options['fields']['changed']['alter']['alter_text'] = 0;
$handler->display->display_options['fields']['changed']['alter']['make_link'] = 0;
$handler->display->display_options['fields']['changed']['alter']['absolute'] = 0;
$handler->display->display_options['fields']['changed']['alter']['trim'] = 0;
$handler->display->display_options['fields']['changed']['alter']['word_boundary'] = 1;
$handler->display->display_options['fields']['changed']['alter']['ellipsis'] = 1;
$handler->display->display_options['fields']['changed']['alter']['strip_tags'] = 0;
$handler->display->display_options['fields']['changed']['alter']['html'] = 0;
$handler->display->display_options['fields']['changed']['hide_empty'] = 0;
$handler->display->display_options['fields']['changed']['empty_zero'] = 0;
$handler->display->display_options['fields']['changed']['date_format'] = 'raw time ago';
/* Field: Workbench Access: Edit link */
$handler->display->display_options['fields']['edit']['id'] = 'edit';
$handler->display->display_options['fields']['edit']['table'] = 'workbench_access';
$handler->display->display_options['fields']['edit']['field'] = 'edit';
$handler->display->display_options['fields']['edit']['label'] = 'Edit';
$handler->display->display_options['fields']['edit']['alter']['alter_text'] = 0;
$handler->display->display_options['fields']['edit']['alter']['make_link'] = 0;
$handler->display->display_options['fields']['edit']['alter']['absolute'] = 0;
$handler->display->display_options['fields']['edit']['alter']['trim'] = 0;
$handler->display->display_options['fields']['edit']['alter']['word_boundary'] = 1;
$handler->display->display_options['fields']['edit']['alter']['ellipsis'] = 1;
$handler->display->display_options['fields']['edit']['alter']['strip_tags'] = 0;
$handler->display->display_options['fields']['edit']['alter']['html'] = 0;
$handler->display->display_options['fields']['edit']['hide_empty'] = 0;
$handler->display->display_options['fields']['edit']['empty_zero'] = 0;
/* Filter: Node: Title */
$handler->display->display_options['filters']['title']['id'] = 'title';
$handler->display->display_options['filters']['title']['table'] = 'node';
$handler->display->display_options['filters']['title']['field'] = 'title';
$handler->display->display_options['filters']['title']['operator'] = 'contains';
$handler->display->display_options['filters']['title']['exposed'] = TRUE;
$handler->display->display_options['filters']['title']['expose']['label'] = 'Title';
$handler->display->display_options['filters']['title']['expose']['identifier'] = 'title';
$handler->display->display_options['filters']['title']['expose']['remember'] = 1;
$handler->display->display_options['filters']['title']['case'] = 1;
/* Filter: Node: Type */
$handler->display->display_options['filters']['type']['id'] = 'type';
$handler->display->display_options['filters']['type']['table'] = 'node';
$handler->display->display_options['filters']['type']['field'] = 'type';
$handler->display->display_options['filters']['type']['exposed'] = TRUE;
$handler->display->display_options['filters']['type']['expose']['label'] = 'Type';
$handler->display->display_options['filters']['type']['expose']['identifier'] = 'type';
$handler->display->display_options['filters']['type']['expose']['remember'] = 1;
$handler->display->display_options['filters']['type']['expose']['reduce'] = 0;
/* Filter: Node: Published */
$handler->display->display_options['filters']['status']['id'] = 'status';
$handler->display->display_options['filters']['status']['table'] = 'node';
$handler->display->display_options['filters']['status']['field'] = 'status';
$handler->display->display_options['filters']['status']['value'] = 'All';
$handler->display->display_options['filters']['status']['exposed'] = TRUE;
$handler->display->display_options['filters']['status']['expose']['label'] = 'Published';
$handler->display->display_options['filters']['status']['expose']['identifier'] = 'status';
$handler->display->display_options['filters']['status']['expose']['remember'] = 1;
/* Filter: Workbench Access: Access filter */
$handler->display->display_options['filters']['access_id']['id'] = 'access_id';
$handler->display->display_options['filters']['access_id']['table'] = 'workbench_access';
$handler->display->display_options['filters']['access_id']['field'] = 'access_id';
$handler->display->display_options['filters']['access_id']['operator'] = 'or';
$handler->display->display_options['filters']['access_id']['exposed'] = TRUE;
$handler->display->display_options['filters']['access_id']['expose']['label'] = 'Sections';
$handler->display->display_options['filters']['access_id']['expose']['identifier'] = 'access_id';
$handler->display->display_options['filters']['access_id']['expose']['remember'] = 1;
$handler->display->display_options['filters']['access_id']['expose']['single'] = 0;
$handler->display->display_options['filters']['access_id']['access_id'] = array();
$handler->display->display_options['filters']['access_id']['size'] = '5';
