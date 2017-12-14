<?php

namespace Drupal\Tests\workbench_access\Functional;

use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\TermInterface;
use Drupal\Tests\BrowserTestBase;
use Drupal\workbench_access\Entity\AccessScheme;
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
   * Test user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * Test user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user2;

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
        'vid' => 'editorial_section',
        'name' => $section . ' term',
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
    $this->user->set(WorkbenchAccessManagerInterface::FIELD_NAME, array_values(array_map(function (TermInterface $term) {
      return 'editorial_section:' . $term->id();
    }, $this->terms)));
    $this->user->save();

    $this->user2 = $this->createUser($permissions);
    $this->user2->set(WorkbenchAccessManagerInterface::FIELD_NAME, ['editorial_section:' . reset($this->terms)->id()]);
    $this->user2->save();
  }

  /**
   * Tests field and filter.
   */
  public function testFieldAndFilter() {
    $this->drupalLogin($this->user);
    $this->drupalGet('admin/content/sections');
    $assert = $this->assertSession();
    foreach ($this->terms as $section => $term) {
      $row = $assert->elementExists('css', '.views-row:contains("' . $term->label() . '")');
      $assert->elementExists('css', '.views-row:contains("' . $section . ' node 1' . '")', $row);
    }
    // Now filter the page.
    $this->drupalGet('admin/content/sections', ['query' => [
      'section' => $this->terms['Some section']->id(),
    ]]);
    $assert->pageTextContains('Some section node 1');
    $assert->pageTextContains('Some section node 2');
    $assert->elementNotExists('css', '.views-row:contains("Another section")');
    $assert->elementNotExists('css', '.views-row:contains("More sections")');
    // Now test as user 2 who only has access to the first section.
    $this->drupalLogin($this->user2);
    $this->drupalGet('admin/content/sections');
    $assert->pageTextContains('Some section node 1');
    $assert->pageTextContains('Some section node 2');
    $assert->elementNotExists('css', '.views-row:contains("Another section")');
    $assert->elementNotExists('css', '.views-row:contains("More sections")');
  }

}
