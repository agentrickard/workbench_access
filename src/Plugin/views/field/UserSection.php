<?php

namespace Drupal\workbench_access\Plugin\views\field;

use Drupal\workbench_access\Entity\AccessSchemeInterface;
use Drupal\workbench_access\Plugin\views\field\Section;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use Drupal\workbench_access\WorkbenchAccessManager;

/**
 * Field handler to present the section assigned to the user.
 *
 * This is a very simple handler, mainly for testing.
 * @TODO: Convert this to using a proper multi-value handler.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("workbench_access_user_section")
 */
class UserSection extends Section {

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    FieldPluginBase::init($view, $display, $options);

    $this->additional_fields['uid'] = 'uid';
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $uid = $this->getValue($values, 'uid');
    $manager = \Drupal::getContainer()->get('plugin.manager.workbench_access.scheme');
    $schemes = \Drupal::entityTypeManager()
      ->getStorage('access_scheme')
      ->loadMultiple();
    if ($schemes) {
      $all = array_reduce($schemes, function (array $items, AccessSchemeInterface $scheme) {
        // @todo this needs to retain keys.
        // @todo this needs to respect IDs in each scheme.
        // @todo this needs to be derivative based
        return array_unique(array_merge($items, $scheme->getAccessScheme()->getTree()));
      }, []);
      if ($manager->userInAll($uid)) {
        $sections = WorkbenchAccessManager::getAllSections($this->scheme, TRUE);
      }
      else {
        $user_section_storage = \Drupal::getContainer()->get('workbench_access.user_section_storage');
        $sections = $user_section_storage->getUserSections($uid);
      }
      $output = [];
      foreach ($sections as $id) {
        foreach ($all as $root => $data) {
          if (isset($data[$id])) {
            $output[] = $this->sanitizeValue($data[$id]['label']);
          }
        }
      }
      return trim(implode($this->options['separator'], $output), $this->options['separator']);
    }
    return '';
  }

}
