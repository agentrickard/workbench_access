<?php
/**
 * @file
 * Contains \Drupal\workbench_access\WorkbenchAccessServiceProvider.
 */

namespace Drupal\workbench_access;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\workbench_access\WorkbenchAccessMenuStorage;

/**
 * Makes the menu tree storage service public.
 */
class WorkbenchAccessServiceProvider extends ServiceProviderBase implements ServiceModifierInterface {
  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    // We have to load full menu data, which has to be done from the private
    // menu.tree_storage service. See core_services.yml.
    $definition = $container->getDefinition('menu.tree_storage');
    $definition->setPublic(TRUE);
    $definition->setClass(WorkbenchAccessMenuStorage::class);
  }

}

