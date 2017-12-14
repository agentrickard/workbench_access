<?php

namespace Drupal\Tests\workbench_access\Functional;

use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Drupal\Tests\BrowserTestBase;
use Drupal\workbench_access\WorkbenchAccessManagerInterface;

/**
 * Defines a class for testing workbench access views
 *
 * @group workbench_access
 */
class ViewsFieldTest extends BrowserTestBase {

  /**
   * Test terms.
   *
   * @var \Drupal\taxonomy\TermInterface[]
   */
  protected $terms = [];

  /**
   * Test nodes.
   *
   * @var \Drupal\node\NodeInterface[]
   */
  protected $nodes = [];

  /**
   * Logged in user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'workbench_access',
    'views',
    'node',
    'taxonomy',
    'system',
    'user',
    'filter',
    'workbench_access_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // Create some sections and some nodes in them.
    $sections = [
      'Some section',
      'Another section',
      'More sections',
    ];
    foreach ($sections as $section) {
      $this->terms[$section] = Term::create([
        'vid' => 'editorial_Section',
        'name' => $section,
      ]);
      $this->terms[$section]->save();
      foreach ([' node 1', ' node 2'] as $stub) {
        $title = $section . $stub;
        $this->nodes[$title] = Node::create([
          'type' => 'article',
          'title' => $title,
          'status' => 1,
          'field_workbench_access' => $this->terms[$section],
        ]);
        $this->nodes[$title]->save();
      }
    }

    // Create a user who can access content etc.
    $permissions = [
      'create article content',
      'edit any article content',
      'access content',
      'delete any article content',
      'administer nodes',
    ];
    $this->user = $this->createUser($permissions);
    $this->user->{WorkbenchAccessManagerInterface::FIELD_NAME} = 'editorial_section:' . $this->terms['Some section']->id();
    $this->user->save();
    $this->drupalLogin($this->user);
  }

  /**
   * Tests field and filter.
   */
  public function testFieldAndFilter() {
    $this->drupalGet('admin/content/sections');
    $assert = $this->assertSession();
    foreach ($this->nodes as $node) {
      $assert->pageTextContains($node->label());
    }
    foreach ($this->terms as $term) {
      $assert->pageTextContains($term->label());
    }
    // Now filter the page.
    $this->drupalGet('admin/content/sections', ['query' => [
      'section' => $this->terms['Some section']->id(),
    ]]);
    $assert->pageTextContains('Some section node 1');
    $assert->pageTextContains('Some section node 2');
    $assert->pageTextNotContains('Another section');
    $assert->pageTextNotContains('More sections');
  }

}
