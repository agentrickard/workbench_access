<?php

namespace Drupal\workbench_access\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;

/**
 * Field handler to present the section assigned to the node.
 *
 * This is a very simple handler, mainly for testing.
 * @TODO: Convert this to using a proper multi-value handler.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("workbench_access_section")
 */
class Section extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    $this->additional_fields['nid'] = 'nid';
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $form['separator'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Separator'),
      '#default_value' => $this->options['separator'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['separator'] = [
      'default' => ', '
    ];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();
    $this->addAdditionalFields();
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $nid = $this->getValue($values, 'nid');
    $manager = \Drupal::getContainer()->get('plugin.manager.workbench_access.scheme');
    if ($scheme = $manager->getActiveScheme()) {
      $node = \Drupal::entityTypeManager()->getStorage('node')->load($nid);
      $sections = $scheme->getEntityValues($node);
      // @todo this should be given to the plugins to handle which can be done
      //   once they become derivative based.
      $tree = $manager->getActiveTree();
      foreach ($sections as $id) {
        foreach ($tree as $root => $data) {
          if (isset($data[$id])) {
            $output[] = $this->sanitizeValue($data[$id]['label']);
          }
        }
      }
      if (isset($output)) {
        return trim(implode($this->options['separator'], $output), $this->options['separator']);
      }
    }
    return '';
  }

}
