<?php

/**
 * @file
 * Contains \Drupal\workbench_access\Controller\WorkbenchAccessSections.
 */

namespace Drupal\workbench_access\Controller;

use Drupal\workbench_access\WorkbenchAccessManagerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

/**
 * Generates the sections list page.
 */
class WorkbenchAccessSections extends ControllerBase {

  /**
   * Returns the section assignment page.
   */
  public function page() {
    $config = $this->config('workbench_access.settings');
    $scheme_id = $config->get('scheme');
    $this->manager = \Drupal::getContainer()->get('plugin.manager.workbench_access.scheme');
    $scheme = $this->manager->getScheme($scheme_id);
    $parents = $config->get('parents');
    $tree = $scheme->getTree();
    $list = '';
    foreach ($parents as $id => $label) {
      // @TODO: Move to a theme function?
      // @TODO: format plural
      foreach ($tree[$id] as $iid => $item) {
        $editor_count = count($this->manager->getEditors($iid));
        $role_count = count($this->manager->getRoles($iid));
        $row = [];
        $row[] = str_repeat('-', $item['depth']) . ' ' . $item['label'];
        $row[] = \Drupal::l($this->t('@count editors', ['@count' => $editor_count]), Url::fromRoute('workbench_access.by_user', array('id' => $iid))); // List of all editors.
        $row[] = \Drupal::l($this->t('@count roles', ['@count' => $role_count]), Url::fromRoute('workbench_access.by_role', array('id' => $iid))); // List of all editors.
        $rows[] = $row;
      }
    }

    $build = array(
      '#type' => 'table',
      '#header' => array($config->get('plural_label'), t('Editors'), t('Roles')),
      '#rows' => $rows,
    );
    return $build;
  }
}
