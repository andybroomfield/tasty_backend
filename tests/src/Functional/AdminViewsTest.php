<?php

namespace Drupal\Tests\tasty_backend\Functional;

use Drupal\Tests\BrowserTestBase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Test the admin views are set up.
 *
 * @group tasty_backend
 */
class AdminViewsTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'tasty_backend',
  ];

  /**
   * A user with the 'content_admin' role.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $contentAdmin;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
  
    // Set up content admin user.
    $this->contentAdmin = $this->drupalCreateUser();
    $this->contentAdmin->addRole('content_admin');
    $this->contentAdmin->save();
  }

  /**
   * Content type admin views set are set up.
   */
  public function testContentTypeAdminViews() {
    
    // Create content types.
    $types = [
      [
        'type' => 'test_type_1_' . strtolower($this->randomMachineName(8)),
        'name' => 'Test Type 1 - ' . $this->randomMachineName(8),
      ],
      [
        'type' => 'test_type_2_' . strtolower($this->randomMachineName(8)),
        'name' => 'Test Type 2 - ' . $this->randomMachineName(8),
      ],
      [
        'type' => 'test_type_3_' . strtolower($this->randomMachineName(8)),
        'name' => 'Test Type 3 - ' . $this->randomMachineName(8),
      ],
    ];
    foreach ($types as $type) {
      $this->createContentType($type);
    }

    // Caches need to be flushed after creating types.
    // @todo check if this is required in main module or just the test.
    drupal_flush_all_caches();

    // Create test content and assign accross each content type.
    $nodes = [];
    for ($i = 0; $i <= 8; $i++) {
      $type_to_assign = $types[$i % 3]['type'];
      $nodes[$i] = $this->createNode([
        'title' => 'Test Content of type ' . $type_to_assign . ' - ' . $this->randomMachineName(6),
        'type' => $type_to_assign,
      ]);
      $nodes[$i]->save();
    }

    // Login as content admin user.
    $this->drupalLogin($this->contentAdmin);

    // Check the admin views are present.
    foreach ($types as $type) {

      // Go to the manage content view of the content type.
      $this->drupalGet('/admin/manage/content/' . $type['type']);

      // Check view returns 200.
      $this->assertSession()->statusCodeEquals(Response::HTTP_OK);

      // Check view title.
      $this->assertSession()->pageTextContains('Manage ' . $type['name'] . ' content');

      // Check view only shows the nodes from the designated type.
      foreach ($nodes as $node) {
        $label = $node->getTitle();
        $url = $node->toUrl()->toString();
        $edit_url = $node->toUrl('edit-form')->toString();
        $bundle = $node->bundle();

        // If node is in the bundle, check it is in the view.
        if ($bundle == $type['type']) {
          $this->assertSession()->linkExists($label);
          $this->assertSession()->linkByHrefExists($url);
          $this->assertSession()->linkByHrefExists($edit_url);
        }

        // Else check the node is not in the view.
        else {
          $this->assertSession()->linkNotExists($label);
          $this->assertSession()->linkByHrefNotExists($url);
          $this->assertSession()->linkByHrefNotExists($edit_url);
        }
      }
    }

    // Check deleting a content type deletes the view.
    // Delete all content first to allow content types to be removed.
    foreach ($nodes as $node) {
      $node->delete();
    }
    foreach ($types as $type) {

      // Delete the content type.
      $this->container->get('entity_type.manager')
        ->getStorage('node_type')
        ->load($type['type'])
        ->delete();

      // Caches need to be flushed after deleting types.
      // @todo check if this is required in main module or just the test.
      drupal_flush_all_caches();
      
      // Go to the manage content view of the content type.
      $this->drupalGet('/admin/manage/content/' . $type['type']);

      // Check view returns 404.
      $this->assertSession()->statusCodeEquals(Response::HTTP_NOT_FOUND);
    }
  }

}
