<?php

namespace Drupal\Tests\tasty_backend\Functional;

use Drupal\Tests\BrowserTestBase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Test the content and user permissions.
 *
 * @group tasty_backend
 */
class PermissionsTest extends BrowserTestBase {

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
   * A user with the 'content_admin' role.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $userAdmin;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
  
    // Set up content admin user.
    $this->contentAdmin = $this->drupalCreateUser();
    $this->contentAdmin->addRole('content_admin');
    $this->contentAdmin->save();

    // Set up user admin user.
    $this->userAdmin = $this->drupalCreateUser();
    $this->userAdmin->addRole('user_admin');
    $this->userAdmin->save();
  }

  /**
   * Content type permissions.
   */
  public function testContentTypePermissions() {
    
    // Create content types.
    $type = [
      'type' => 'test_type_1_' . strtolower($this->randomMachineName(8)),
      'name' => 'Test Type 1 - ' . $this->randomMachineName(8),
    ];;
    $this->createContentType($type);

    // Caches need to be flushed after creating types.
    // @todo check if this is required in main module or just the test.
    drupal_flush_all_caches();

    // Create test content.
    $published_node = $this->createNode([
      'title' => 'Test Published Page - ' . $this->randomMachineName(6),
      'type' => $type['type'],
    ]);
    $published_node->save();
    $unpublished_node = $this->createNode([
      'title' => 'Test Published Page - ' . $this->randomMachineName(6),
      'type' => $type['type'],
      'status' => 0,
    ]);
    $unpublished_node->save();

    $published_node_edit_url = $published_node->toUrl('edit-form')->toString();
    $published_node_delete_url = $published_node->toUrl('delete-form')->toString();
    $unpublished_node_url = $unpublished_node->toUrl()->toString();

    // Login as content admin user.
    $this->drupalLogin($this->contentAdmin);

    // Check the admin view returns 200.
    $this->drupalGet('/admin/manage/content/' . $type['type']);
    $this->assertSession()->statusCodeEquals(Response::HTTP_OK);

    // Check content admin can create content of type.
    $this->drupalGet('/node/add/' . $type['type']);
    $this->assertSession()->statusCodeEquals(Response::HTTP_OK);

    // Check content admin can edit the published page.
    $this->drupalGet($published_node_edit_url);
    $this->assertSession()->statusCodeEquals(Response::HTTP_OK);

    // Check content admin can delete the published page.
    $this->drupalGet($published_node_delete_url);
    $this->assertSession()->statusCodeEquals(Response::HTTP_OK);

    // Check content admin can view the unpublished page.
    $this->drupalGet($unpublished_node_url);
    $this->assertSession()->statusCodeEquals(Response::HTTP_OK);

    $this->drupalLogout();

    // Login as user admin user.
    $this->drupalLogin($this->userAdmin);

    // Check the admin view returns 403.
    $this->drupalGet('/admin/manage/content/' . $type['type']);
    $this->assertSession()->statusCodeEquals(Response::HTTP_FORBIDDEN);

    // Check user admin cannot create content of type.
    $this->drupalGet('/node/add/' . $type['type']);
    $this->assertSession()->statusCodeEquals(Response::HTTP_FORBIDDEN);

    // Check user admin cannot edit the published page.
    $this->drupalGet($published_node_edit_url);
    $this->assertSession()->statusCodeEquals(Response::HTTP_FORBIDDEN);

    // @todo Test node options are accessible here.

    // Check user admin cannot delete the published page.
    $this->drupalGet($published_node_delete_url);
    $this->assertSession()->statusCodeEquals(Response::HTTP_FORBIDDEN);

    // Check user admin cannot view the unpublished page.
    $this->drupalGet($unpublished_node_url);
    $this->assertSession()->statusCodeEquals(Response::HTTP_FORBIDDEN);
  }

  public function testUserManagementPermissions() {

    // Set up an authenticated user for this test.
    $authenticated_user = $this->drupalCreateUser();
    $authenticated_user_edit_url = $authenticated_user->toUrl('edit-form')->toString();
    $authenticated_user_cancel_url = $authenticated_user->toUrl('cancel-form')->toString();

    // Login as user admin user.
    $this->drupalLogin($this->userAdmin);

    // Check user admin can access user view
    $this->drupalGet('/admin/manage/users');
    $this->assertSession()->statusCodeEquals(Response::HTTP_OK);

    // Check user admin can access create a user
    $this->drupalGet('/admin/manage/users/create');
    $this->assertSession()->statusCodeEquals(Response::HTTP_OK);

    // Check user admin can edit a user
    $this->drupalGet($authenticated_user_edit_url);
    $this->assertSession()->statusCodeEquals(Response::HTTP_OK);

    // @todo Add check for assigning roles.

    // Check user admin can delete a user
    $this->drupalGet($authenticated_user_cancel_url);
    $this->assertSession()->statusCodeEquals(Response::HTTP_OK);

    $this->drupalLogout();

    // Login as content admin user.
    $this->drupalLogin($this->contentAdmin);

    // Check content admin cannot access user view
    $this->drupalGet('/admin/manage/users');
    $this->assertSession()->statusCodeEquals(Response::HTTP_FORBIDDEN);

    // Check content admin cannot access create a user
    $this->drupalGet('/admin/manage/users/create');
    $this->assertSession()->statusCodeEquals(Response::HTTP_FORBIDDEN);

    // Check content admin cannot edit a user
    $this->drupalGet($authenticated_user_edit_url);
    $this->assertSession()->statusCodeEquals(Response::HTTP_FORBIDDEN);

    // Check content admin cannot delete a user
    $this->drupalGet($authenticated_user_cancel_url);
    $this->assertSession()->statusCodeEquals(Response::HTTP_FORBIDDEN);
  }
}