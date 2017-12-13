<?php

namespace Drupal\Tests\workbench_access\Functional;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\NodeType;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\workbench_access\Entity\AccessScheme;
use Drupal\workbench_access\WorkbenchAccessManagerInterface;

/**
 * Contains helper classes for tests to set up various configuration.
 */
trait WorkbenchAccessTestTrait {

  /**
   * Set up a content type with workbench access enabled.
   *
   * @return \Drupal\node\Entity\NodeType
   *   The node type entity.
   */
  public function setUpContentType() {
    $node_type = $this->createContentType(['type' => 'page']);

    return $node_type;
  }

  /**
   * Create a test vocabulary.
   *
   * @return \Drupal\taxonomy\Entity\Vocabulary
   *   The vocabulary entity.
   */
  public function setUpVocabulary() {
    $vocab = Vocabulary::create(['vid' => 'workbench_access', 'name' => 'Access']);
    $vocab->save();
    return $vocab;
  }

  /**
   * Sets up a taxonomy field on a given entity type and bundle.
   *
   * @param string $entity_type_id
   *   Entity type ID.
   * @param string $bundle
   *   Bundle ID.
   * @param string $vocabulary_id
   *   Vocabulary ID.
   * @param string $field_name
   *   Field name.
   */
  protected function setUpTaxonomyFieldForEntityType($entity_type_id, $bundle, $vocabulary_id, $field_name = WorkbenchAccessManagerInterface::FIELD_NAME) {
    if (!$field_storage = FieldStorageConfig::load("$entity_type_id.$field_name")) {
      $field_storage = FieldStorageConfig::create([
        'field_name' => $field_name,
        'entity_type' => $entity_type_id,
        'type' => 'entity_reference',
        'cardinality' => 1,
        'settings' => [
          'target_type' => 'taxonomy_term',
        ],
      ]);
    $field_storage->save();
    }
    if (!$field = FieldConfig::load("$entity_type_id.$bundle.$field_name")) {
      // Create an instance of the access field on the bundle.
      $field = FieldConfig::create([
        'field_storage' => $field_storage,
        'bundle' => $bundle,
        'settings' => [
          'handler' => 'workbench_access:taxonomy_term',
          'handler_settings' => [
            'target_bundles' => [
              $vocabulary_id => $vocabulary_id,
            ],
          ],
        ],
      ]);
      $field->save();
    }
    // Set the field to display as a dropdown on the form.
    if (!$form_display = EntityFormDisplay::load("$entity_type_id.$bundle.default")) {
      $form_display = EntityFormDisplay::create([
        'targetEntityType' => $entity_type_id,
        'bundle' => $bundle,
        'mode' => 'default',
        'status' => TRUE,
      ]);
    }
    $form_display->setComponent($field_name, ['type' => 'options_select']);
    $form_display->save();
  }

  /**
   * Sets up a user with an editor role that has access to content.
   *
   * @return \Drupal\user\Entity\User
   *   The user entity.
   */
  public function setUpEditorUser() {
    $editor_rid = $this->createRole([
      'access administration pages',
      'create page content',
      'edit any page content',
      'delete any page content',
    ], 'editor');

    return $this->createUserWithRole($editor_rid);
  }

  /**
   * Sets up a user with an editor role that has access to content.
   *
   * @param array $additional_permissions
   *   Array of additional permissions beyond 'access administration pages' and
   *   'assign workbench access'.
   *
   * @return \Drupal\user\Entity\User
   *   The user entity.
   */
  protected function setUpAdminUser($additional_permissions = []) {
    $admin_rid = $this->createRole(array_merge($additional_permissions, [
      'access administration pages',
      'assign workbench access',
    ]), 'admin');

    return $this->createUserWithRole($admin_rid);
  }

  /**
   * Sets up a user with a given role and saves it.
   *
   * @param string $rid
   *    The role id.
   *
   * @return \Drupal\user\Entity\User
   *   The user entity.
   */
  protected function createUserWithRole($rid) {
    $user = $this->createUser();
    $user->addRole($rid);
    $user->save();
    return $user;
  }

  /**
   * Sets up a taxonomy scheme for a given node type.
   *
   * @param \Drupal\taxonomy\Entity\Vocabulary $vocab
   *   The vocab to use for the scheme.
   */
  public function setUpTaxonomyScheme(NodeType $node_type, Vocabulary $vocab) {
    $scheme = AccessScheme::create([
      'id' => 'editorial_section',
      'label' => 'Editorial section',
      'plural_label' => 'Editorial sections',
      'scheme' => 'taxonomy',
      'scheme_settings' => [
        'vocabularies' => [$vocab->id()],
        'fields' => [
          [
            'entity_type' => 'node',
            'field' => WorkbenchAccessManagerInterface::FIELD_NAME,
            'bundle' => $node_type->id(),
          ],
        ],
      ],
    ]);
    $scheme->save();
  }

}
