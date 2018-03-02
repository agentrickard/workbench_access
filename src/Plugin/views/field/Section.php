<?php

namespace Drupal\workbench_access\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\workbench_access\Entity\AccessSchemeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Field handler to present the section assigned to the node.
 *
 * This is a very simple handler, mainly for testing.
 *
 * @TODO: Convert this to use a proper multi-value handler.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("workbench_access_section")
 */
class Section extends FieldPluginBase {

  /**
   * Scheme.
   *
   * @var \Drupal\workbench_access\Entity\AccessSchemeInterface
   */
  protected $scheme;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var self $instance */
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    return $instance->setScheme($container->get('entity_type.manager')->getStorage('access_scheme')->load($configuration['scheme']));
  }

  /**
   * Sets access scheme.
   *
   * @param \Drupal\workbench_access\Entity\AccessSchemeInterface $scheme
   *   Access scheme.
   *
   * @return $this
   */
  public function setScheme(AccessSchemeInterface $scheme) {
    $this->scheme = $scheme;
    return $this;
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
      'default' => ', ',
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
    if ($entity = $this->getEntity($values)) {
      $scheme = $this->scheme->getAccessScheme();
      $sections = $scheme->getEntityValues($entity);
      $tree = $scheme->getTree();
      foreach ($sections as $id) {
        foreach ($tree as $root => $data) {
          if (isset($data[$id])) {
            // @TODO: This is being sanitzed on output.
            if (isset($data[$id]['entity_uri'])) {
              $output[] = \Drupal::l($data[$id]['label'], Url::fromUri($data[$id]['entity_uri']));
            }
            else {
              $output[] = $this->sanitizeValue($data[$id]['label']);
            }
          }
        }
      }
      if (isset($output)) {
        return trim(implode($this->options['separator'], $output), $this->options['separator']);
      }
    }
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = [];
    $dependencies[$this->scheme->getConfigDependencyKey()][] = $this->scheme->getConfigDependencyName();
    return $dependencies;
  }

}
