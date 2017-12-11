<?php

namespace Drupal\workbench_access\Plugin\Deriver;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a plugin deriver for the workbench access section.
 */
class SectionViewsPluginDeriver extends DeriverBase implements ContainerDeriverInterface {

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Base plugin ID.
   *
   * @var string
   */
  protected $basePluginId;

  /**
   * Constructs a new SectionViewsPluginDeriver object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   * @param string $base_plugin_id
   *   Base plugin ID.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, $base_plugin_id) {
    $this->entityTypeManager = $entityTypeManager;
    $this->basePluginId = $base_plugin_id;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity_type.manager'),
      $base_plugin_id
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    foreach ($this->entityTypeManager->getStorage('access_scheme')->loadMultiple() as $id => $scheme) {
      $this->derivatives[sprintf('%s:%s', $this->basePluginId, $id)] = [
        'scheme' => $id,
      ] + $base_plugin_definition;
    }
    return $this->derivatives;
  }

}
