<?php

namespace Drupal\Tests\tasty_backend\Functional;

use Drupal\system\Entity\Menu;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\BrowserTestBase;

/**
 * Test the toolbar menu.
 *
 * @group tasty_backend
 */
class ToolbarMenuTest extends BrowserTestBase {

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

    // Set up user admin user.
    $this->userAdmin = $this->drupalCreateUser();
    $this->userAdmin->addRole('user_admin');
    $this->userAdmin->save();
  }

  /**
   * Content type admin views set are set up.
   */
  public function testToolbarMenu() {
    
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

    // Add taxonomy vocabularies.
    $taxonomies = [
      [
        'vid' => 'test_taxonomy_1_' . strtolower($this->randomMachineName(8)),
        'name' => 'Test Taxonomy 1 - ' . $this->randomMachineName(8),
      ],
      [
        'vid' => 'test_taxonomy_2_' . strtolower($this->randomMachineName(8)),
        'name' => 'Test Taxonomy 2 - ' . $this->randomMachineName(8),
      ],
      [
        'vid' => 'test_taxonomy_3_' . strtolower($this->randomMachineName(8)),
        'name' => 'Test Taxonomy 3 - ' . $this->randomMachineName(8),
      ],
    ];
    foreach ($taxonomies as $taxonomy) {
      Vocabulary::create($taxonomy)->save();
    }

    // Add menus.
    $menus = [
      [
        'id' => 'test_menu_1_' . strtolower($this->randomMachineName(8)),
        'label' => 'Test Menu 1 - ' . $this->randomMachineName(8),
      ],
      [
        'id' => 'test_menu_2_' . strtolower($this->randomMachineName(8)),
        'label' => 'Test Menu 2 - ' . $this->randomMachineName(8),
      ],
      [
        'id' => 'test_menu_3_' . strtolower($this->randomMachineName(8)),
        'label' => 'Test Menu 3 - ' . $this->randomMachineName(8),
      ],
    ];
    foreach ($menus as $menu) {
      Menu::create($menu)->save();
      user_role_grant_permissions('content_admin', [
        'administer ' . $menu['id'] . ' menu items',
      ]);
    }

    // Caches need to be flushed after creating types.
    // @todo check if this is required in main module or just the test.
    drupal_flush_all_caches();

    // Login as content admin user.
    $this->drupalLogin($this->contentAdmin);

    // Check the Add content menu item is present.
    $add_content_xpath = './/*[@id="toolbar-item-toolbar-menu-tb-manage-tray"]//*[contains(concat(" ",normalize-space(@class)," ")," toolbar-menu ")]/*[contains(concat(" ",normalize-space(@class)," ")," menu-item ")]/*[contains(@href, "/node/add")]';
    $query = $this->xpath($add_content_xpath);
    $path = $query[0]->getAttribute('data-drupal-link-system-path');
    $title = $query[0]->getAttribute('title');
    $this->assertEquals('node/add', $path);
    $this->assertEquals('Add content', $title);

    // Check the Manage content menu item is present.
    $manage_content_xpath = './/*[@id="toolbar-item-toolbar-menu-tb-manage-tray"]//*[contains(concat(" ",normalize-space(@class)," ")," toolbar-menu ")]/*[contains(concat(" ",normalize-space(@class)," ")," menu-item ")]/*[contains(@href, "/admin/manage/content")]';
    $query = $this->xpath($manage_content_xpath);
    $path = $query[0]->getAttribute('data-drupal-link-system-path');
    $title = $query[0]->getAttribute('title');
    $this->assertEquals('admin/manage/content', $path);
    $this->assertEquals('Manage content', $title);

    // Check the add and manage content links are present for each content type.
    foreach ($types as $type) {

      // Check a node/add/type link is present under Add content.
      $query = $this->xpath('.//*[@id="toolbar-item-toolbar-menu-tb-manage-tray"]//*[contains(concat(" ",normalize-space(@class)," ")," toolbar-menu ")]/*[contains(concat(" ",normalize-space(@class)," ")," menu-item ")]/*[contains(@href, "/node/add")]/following-sibling::*[1]/self::*[contains(concat(" ",normalize-space(@class)," ")," toolbar-menu ")]/*[contains(concat(" ",normalize-space(@class)," ")," menu-item ")]/a[contains(@href, "/node/add/' . $type['type'] . '")]');
      $path = $query[0]->getAttribute('data-drupal-link-system-path');
      $value = $query[0]->getText();
      $this->assertEquals('node/add/' . $type['type'], $path);
      $this->assertEquals($type['name'], $value);

      // Check a admin/manage/content/type link is present under Manage content.
      $query = $this->xpath('.//*[@id="toolbar-item-toolbar-menu-tb-manage-tray"]//*[contains(concat(" ",normalize-space(@class)," ")," toolbar-menu ")]/*[contains(concat(" ",normalize-space(@class)," ")," menu-item ")]/*[contains(@href, "/admin/manage/content")]/following-sibling::*[1]/self::*[contains(concat(" ",normalize-space(@class)," ")," toolbar-menu ")]/*[contains(concat(" ",normalize-space(@class)," ")," menu-item ")]/a[contains(@href, "/admin/manage/content/' . $type['type'] . '")]');
      $path = $query[0]->getAttribute('data-drupal-link-system-path');
      $value = $query[0]->getText();
      $this->assertEquals('admin/manage/content/' . $type['type'], $path);
      $this->assertEquals($type['name'], $value);
    }

    // Check the taxonomy menu exists.
    $taxonomy_xpath = './/*[@id="toolbar-item-toolbar-menu-tb-manage-tray"]//*[contains(concat(" ",normalize-space(@class)," ")," toolbar-menu ")]/*[contains(concat(" ",normalize-space(@class)," ")," menu-item ")]/*[contains(@href, "/admin/structure/taxonomy")]';
    $query = $this->xpath($taxonomy_xpath);
    $path = $query[0]->getAttribute('data-drupal-link-system-path');
    $title = $query[0]->getText();
    $this->assertEquals('admin/structure/taxonomy', $path);
    $this->assertEquals('Taxonomy', $title);

    // Check the taxonomy links are present for each vocabulary.
    foreach ($taxonomies as $taxonomy) {

      $query = $this->xpath('.//*[@id="toolbar-item-toolbar-menu-tb-manage-tray"]//*[contains(concat(" ",normalize-space(@class)," ")," toolbar-menu ")]/*[contains(concat(" ",normalize-space(@class)," ")," menu-item ")]/*[contains(@href, "/admin/structure/taxonomy")]/following-sibling::*[1]/self::*[contains(concat(" ",normalize-space(@class)," ")," toolbar-menu ")]/*[contains(concat(" ",normalize-space(@class)," ")," menu-item ")]/a[contains(@href, "/admin/structure/taxonomy/manage/' . $taxonomy['vid'] . '/overview")]');
      $path = $query[0]->getAttribute('data-drupal-link-system-path');
      $value = $query[0]->getText();
      $this->assertEquals('admin/structure/taxonomy/manage/' . $taxonomy['vid'] . '/overview', $path);
      $this->assertEquals($taxonomy['name'], $value);
    }

    // Check the menus menu exists.
    $menu_xpath = './/*[@id="toolbar-item-toolbar-menu-tb-manage-tray"]//*[contains(concat(" ",normalize-space(@class)," ")," toolbar-menu ")]/*[contains(concat(" ",normalize-space(@class)," ")," menu-item ")]/*[contains(@href, "/admin/structure/menu")]';
    $query = $this->xpath($menu_xpath);
    $path = $query[0]->getAttribute('data-drupal-link-system-path');
    $title = $query[0]->getText();
    $this->assertEquals('admin/structure/menu', $path);
    $this->assertEquals('Menus', $title);

    // Check the menu links are present for each menu.
    $default_menus = [
      [
        'id' => 'main',
        'label' => 'Main navigation',
      ],
      [
        'id' => 'footer',
        'label' => 'Footer',
      ],
    ];
    $menus = array_merge($menus, $default_menus);
    foreach ($menus as $menu) {

      $query = $this->xpath('.//*[@id="toolbar-item-toolbar-menu-tb-manage-tray"]//*[contains(concat(" ",normalize-space(@class)," ")," toolbar-menu ")]/*[contains(concat(" ",normalize-space(@class)," ")," menu-item ")]/*[contains(@href, "/admin/structure/menu")]/following-sibling::*[1]/self::*[contains(concat(" ",normalize-space(@class)," ")," toolbar-menu ")]/*[contains(concat(" ",normalize-space(@class)," ")," menu-item ")]/a[contains(@href, "/admin/structure/menu/manage/' . $menu['id'] . '")]');
      $path = $query[0]->getAttribute('data-drupal-link-system-path');
      $value = $query[0]->getText();
      $this->assertEquals('admin/structure/menu/manage/' . $menu['id'], $path);
      $this->assertEquals($menu['label'], $value);
    }

    // Check user menu is not visible.
    $user_xpath = './/*[@id="toolbar-item-toolbar-menu-tb-manage-tray"]//*[contains(concat(" ",normalize-space(@class)," ")," toolbar-menu ")]/*[contains(concat(" ",normalize-space(@class)," ")," menu-item ")]/*[contains(@href, "/admin/manage/users")]';
    $query = $this->xpath($user_xpath);
    $this->assertEmpty($query);

    // Log out as content admin.
    $this->drupalLogout();

    // Login as user admin user.
    $this->drupalLogin($this->userAdmin);

    // Check content menus are not visible.
    $query = $this->xpath($add_content_xpath);
    $this->assertEmpty($query);
    $query = $this->xpath($manage_content_xpath);
    $this->assertEmpty($query);
    $query = $this->xpath($taxonomy_xpath);
    $this->assertEmpty($query);
    $query = $this->xpath($menu_xpath);
    $this->assertEmpty($query);

    // Check the manage user menu is present.
    $query = $this->xpath($user_xpath);
    $path = $query[0]->getAttribute('data-drupal-link-system-path');
    $title = $query[0]->getText();
    $this->assertEquals('admin/manage/users', $path);
    $this->assertEquals('Users', $title);

    // Check the add user link is present.
    $query = $this->xpath('.//*[@id="toolbar-item-toolbar-menu-tb-manage-tray"]//*[contains(concat(" ",normalize-space(@class)," ")," toolbar-menu ")]/*[contains(concat(" ",normalize-space(@class)," ")," menu-item ")]/*[contains(@href, "/admin/manage/users")]/following-sibling::*[1]/self::*[contains(concat(" ",normalize-space(@class)," ")," toolbar-menu ")]/*[contains(concat(" ",normalize-space(@class)," ")," menu-item ")]/a[contains(@href, "/admin/manage/users/create")]');
    $path = $query[0]->getAttribute('data-drupal-link-system-path');
    $title = $query[0]->getText();
    $this->assertEquals('admin/manage/users/create', $path);
    $this->assertEquals('Add user', $title);
  }
}
