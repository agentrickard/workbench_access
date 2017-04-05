<?php

namespace Drupal\workbench_access\Plugin\views\field;

use Drupal\workbench_access\Plugin\views\field\Section;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;

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
    if ($scheme = $manager->getActiveScheme()) {
      if ($manager->userInAll($uid)) {
        $sections = $manager->getAllSections(TRUE);
      }
      else {
        $sections = $manager->getUserSections($uid);
      }
      $output = [];
      $tree = $manager->getActiveTree();
      foreach ($sections as $id) {
        foreach ($tree as $root => $data) {
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
