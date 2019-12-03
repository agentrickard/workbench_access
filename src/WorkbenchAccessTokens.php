<?php

namespace Drupal\workbench_access;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\user\UserInterface;
use Drupal\workbench_access\Entity\AccessSchemeInterface;

/**
 * Token handler for Workbench Access.
 *
 * TokenAPI still uses procedural code, but we have moved it to a class for
 * easier refactoring.
 */
class WorkbenchAccessTokens {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The Domain storage handler.
   *
   * @var \Drupal\domain\DomainStorageInterface
   */
  protected $domainStorage;

  /**
   * The Domain negotiator.
   *
   * @var \Drupal\domain\DomainNegotiatorInterface
   */
  protected $negotiator;

  /**
   * Constructs a DomainToken object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\domain\DomainNegotiatorInterface $negotiator
   *   The domain negotiator.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Implements hook_token_info().
   */
  public function getTokenInfo() {
    $info['tokens'] = [
      'user' => [
        'workbench-access-sections' => [
          'name' => t('Workbench access sections'),
          'description' => $this->t('Section assignments for the user account.'),
          // Optionally use token module's array type which gives users greater
          // control on output.
          'type' => \Drupal::moduleHandler()->moduleExists('token') ? 'array' : '',
        ],
      ],
    ];

    return $info;
  }

  /**
   * Implements hook_tokens().
   */
  public function getTokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
    $token_service = \Drupal::token();

    $replacements = [];

    // User tokens.
    if ($type === 'user' && !empty($data['user'])) {
      /** @var \Drupal\user\UserInterface $user */
      $user = $data['user'];

      foreach ($tokens as $name => $original) {
        switch ($name) {
          case 'workbench-access-sections':
            if ($sections = $this->getUserSectionNames($user, $bubbleable_metadata)) {
              if (function_exists('token_render_array')) {
                $replacements[$original] = token_render_array($sections, $options);
              }
              else {
                $replacements[$original] = implode(', ', $sections);
              }
            }
            break;
        }
      }

      // Chained token relationships.
      if ($section_tokens = $token_service->findWithPrefix($tokens, 'workbench-access-sections')) {
        if ($sections = $this->getUserSectionNames($user, $bubbleable_metadata)) {
          $replacements += $token_service->generate('array', $section_tokens, ['array' => $sections], $options, $bubbleable_metadata);
        }
      }
    }

    return $replacements;
  }

  /**
   * Generates an array of section names for a given account.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user account.
   * @param \Drupal\Core\Render\BubbleableMetadata $bubbleable_metadata
   *   The cache metadata.
   *
   * @return array
   *   An array of section names.
   */
  private function getUserSectionNames(UserInterface $user, BubbleableMetadata $bubbleable_metadata) {
    /** @var \Drupal\workbench_access\UserSectionStorageInterface $user_section_storage */
    $user_section_storage = \Drupal::service('workbench_access.user_section_storage');
    $schemes = \Drupal::entityTypeManager()->getStorage('access_scheme')->loadMultiple();
    return array_reduce($schemes, function (array $carry, AccessSchemeInterface $scheme) use ($user_section_storage, $user, $bubbleable_metadata) {
      if (!$sections = $user_section_storage->getUserSections($scheme, $user)) {
        return $carry;
      }
      $bubbleable_metadata->addCacheableDependency($scheme);

      return array_merge($carry, array_reduce($scheme->getAccessScheme()->getTree(), function ($inner, $info) use ($sections) {
        $user_in_sections = array_intersect_key($info, array_combine($sections, $sections));
        return array_merge($inner, array_column($user_in_sections, 'label'));
      }, []));
    }, []);

  }

}
