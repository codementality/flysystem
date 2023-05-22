<?php

namespace Drupal\Tests\flysystem\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests module installation and uninstallation.
 *
 * @group flysystem
 */
class ModuleInstallUninstallWebTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['flysystem'];

  /**
   * Tests installation and uninstallation.
   */
  public function testInstallationAndUninstallation() {
    $module_handler = \Drupal::moduleHandler();
    $this->assertTrue($module_handler->moduleExists(reset(static::$modules)));

    /** @var \Drupal\Core\Extension\ModuleInstallerInterface $module_installer */
    $module_installer = \Drupal::service('module_installer');

    $module_installer->uninstall(static::$modules);
    $this->assertFalse($module_handler->moduleExists(reset(static::$modules)));
  }

}
