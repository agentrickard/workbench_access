<?php

namespace Drupal\Tests\workbench_access\Functional;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\NodeType;
use Drupal\taxonomy\Entity\Vocabulary;
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
    $node_type->setThirdPartySetting('workbench_access', 'workbench_access_status', 1);
    $node_type->save();

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
   * Sets up an access taxonomy field for a given vocab on a given node type.
   *
   * @param \Drupal\node\Entity\NodeType $node_type
   *   The node type to create the field on.
   * @param \Drupal\taxonomy\Entity\Vocabulary $vocab
   *   The vocab to create the field against.
   */
  public function setUpTaxonomyField(NodeType $node_type, Vocabulary $vocab) {
    // Create workbench access storage for node.
    $field_storage = FieldStorageConfig::create([
      'field_name' => WorkbenchAccessManagerInterface::FIELD_NAME,
      'entity_type' => 'node',
      'type' => 'entity_reference',
      'cardinality' => 1,
      'settings' => [
        'target_type' => 'taxonomy_term',
      ],
    ]);
    $field_storage->save();
    // Create an instance of the access field on the content type.
    FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => $node_type->id(),
      'settings' => [
        'handler' => 'default:taxonomy_term',
        'handler_settings' => [
          'target_bundles' => [
            $vocab->id() => $vocab->id(),
          ],
        ],
      ],
    ])->save();
    // Set the field to display as a dropdown on the form.
    $form_display = EntityFormDisplay::load('node.' . $node_type->id() . '.default');
    $form_display->setComponent(WorkbenchAccessManagerInterface::FIELD_NAME, ['type' => 'options_select']);
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
   * @return \Drupal\user\Entity\User
   *   The user entity.
   */
  protected function setUpAdminUser() {
    $admin_rid = $this->createRole([
      'access administration pages',
      'assign workbench access',
    ], 'admin');

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
    $config = \Drupal::configFactory()->getEditable('workbench_access.settings');
    $config->set('scheme', 'taxonomy');
    $config->set('parents', [$vocab->id() => $vocab->id()]);
    $fields['node'][$node_type->id()] = WorkbenchAccessManagerInterface::FIELD_NAME;
    $config->set('fields', $fields);
    $config->save();
  }

}
