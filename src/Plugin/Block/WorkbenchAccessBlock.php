<?php

namespace Drupal\workbench_access\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Creates a block to show editorial status.
 *
 * @TODO: Replace with Workbench block later.
 *
 * @Block(
 *   id = "workbench_access_block",
 *   admin_label = @Translation("Workbench Access information")
 * )
 */
class WorkbenchAccessBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    if ($node = \Drupal::routeMatch()->getParameter('node')) {
      $manager = \Drupal::service('plugin.manager.workbench_access.scheme');
      if ($scheme = $manager->getActiveScheme()) {

        if ($field = $scheme->fields('node', $node->getType())) {
          foreach ($scheme->getEntityValues($node, $field) as $value) {
            if ($element = $manager->getElement($value)) {
              // @TODO: This needs to be tested better.
              $build['#theme'] = 'item_list';
              $build['#items']['#title'] = $this->t('Editorial sections:');
              $build['#items'][] = $element['label'];
              $build['#plain_text'] = TRUE;
            }
          }
        }
      }
    }
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIfHasPermissions($account, ['administer workbench access', 'view workbench access information'], 'OR');
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return ['url.path'];
  }

}
