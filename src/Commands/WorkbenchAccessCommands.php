<?php

namespace Drupal\workbench_access\Commands;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Drush\Commands\DrushCommands;
use Drupal\Core\Language\LanguageInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * A Drush commandfile for Workbench Access.
 */
class WorkbenchAccessCommands extends DrushCommands {

  /**
   * Installs the workbench_access test vocabulary.
   *
   * @command workbench_access:installTest
   * @aliases wa-test
   */
  public function installTest() {
    try {
      // Create a vocabulary.
      $vocabulary = Vocabulary::create([
        'name' => 'Workbench Access',
        'description' => 'Test taxonomy for Workbench Access',
        'vid' => 'workbench_access',
        'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
        'weight' => 100,
      ]);
      $vocabulary->save();
      // Create some terms.
      $terms = [
        'Alumni',
        'Faculty',
        'Staff',
        'Students',
      ];
      $children = [
        'Directory',
        'Information',
      ];

      $filter_formats = filter_formats();
      $format = array_pop($filter_formats);
      foreach ($terms as $name) {
        $term = Term::create([
          'name' => $name,
          'description' => [],
          'vid' => $vocabulary->id(),
          'parent' => 0,
          'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
        ]);
        $term->save();
        foreach ($children as $child) {
          $child = Term::create([
            'name' => "$name $child",
            'description' => [],
            'vid' => $vocabulary->id(),
            'parent' => $term->id(),
            'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
          ]);
          $child->save();
        }
      }
    }
    catch (Exception $e) {
      $this->logger()->warning(dt('The test vocabulary has already been created.'));
    }
    $this->logger()->success(dt('Workbench Access test vocabulary created.'));
  }

  /**
   * Flushes assigned user permissions.
   *
   * @command workbench_access:flush
   * @aliases wa-flush
   */
  public function flush() {
    $section_storage = \Drupal::entityTypeManager()->getStorage('section_association');
    foreach (\Drupal::entityTypeManager()->getStorage('access_scheme')->loadMultiple() as $scheme) {
      $sections = $section_storage->loadByProperties([
        'access_scheme' => $scheme->id(),
      ]);
      $section_storage->delete($sections);
    }
    $this->logger()->success(dt('User and role assignments cleared.'));
  }
}
