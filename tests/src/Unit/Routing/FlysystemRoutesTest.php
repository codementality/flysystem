<?php

namespace Drupal\Tests\flysystem\Unit\Routing;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\StreamWrapper\LocalStream;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\flysystem\FlysystemFactory;
use Drupal\flysystem\Routing\FlysystemRoutes;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\flysystem\Routing\FlysystemRoutes
 * @group flysystem
 */
class FlysystemRoutesTest extends UnitTestCase {

  /**
   * Flysystem Factory.
   *
   * @var \Drupal\flysystem\FlysystemFactory
   */
  protected $factory;

  /**
   * Drupal ModuleHandler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Flysystem routing for files.
   *
   * @var \Drupal\flysystem\Routing\FlysystemRoutes
   */
  protected $router;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $container = new ContainerBuilder();

    $stream_wrapper = $this->prophesize(LocalStream::class);
    $stream_wrapper->getDirectoryPath()->willReturn('sites/default/files');

    $stream_wrapper_manager = $this->prophesize(StreamWrapperManagerInterface::class);
    $stream_wrapper_manager->getViaScheme('public')->willReturn($stream_wrapper->reveal());

    $this->moduleHandler = $this->prophesize(ModuleHandlerInterface::class);

    $factory = $this->prophesize(FlysystemFactory::class);
    $factory->getSchemes()->willReturn(['test']);

    $container->set('flysystem_factory', $factory->reveal());
    $container->set('stream_wrapper_manager', $stream_wrapper_manager->reveal());
    $container->set('module_handler', $this->moduleHandler->reveal());

    $this->router = FlysystemRoutes::create($container);
  }

  /**
   * @covers ::__construct
   * @covers ::create
   * @covers ::routes
   */
  public function testInvalidSettingsAreSkipped() {
    new Settings([
      'flysystem' => [
        'invalid' => ['driver' => 'local'],
        'test' => ['driver' => 'local'],
      ],
    ]);

    $this->assertSame([], $this->router->routes());
  }

  /**
   * @covers ::routes
   */
  public function testInvalidDriversAreSkipped() {
    new Settings(['flysystem' => ['test' => ['driver' => 'ftp']]]);

    $this->assertSame([], $this->router->routes());
  }

  /**
   * @covers ::routes
   */
  public function testDriversNotPublicAreSkipped() {
    new Settings(['flysystem' => ['test' => ['driver' => 'local']]]);

    $this->assertSame([], $this->router->routes());
  }

  /**
   * @covers ::routes
   */
  public function testLocalPathSameAsPublicIsSkipped() {
    new Settings([
      'flysystem' => [
        'test' => [
          'driver' => 'local',
          'public' => TRUE,
          'config' => [
            'public' => TRUE,
            'root' => 'sites/default/files',
          ],
        ],
      ],
    ]);

    $this->assertSame([], $this->router->routes());
  }

  /**
   * @covers ::routes
   */
  public function testValidRoutesReturned() {
    new Settings([
      'flysystem' => [
        'test' => [
          'driver' => 'local',
          'public' => TRUE,
          'config' => [
            'public' => TRUE,
            'root' => 'sites/default/files/flysystem',
          ],
        ],
      ],
    ]);

    $routes = $this->router->routes();
    $this->assertCount(1, $routes);
    $this->assertTrue(isset($routes['flysystem.test.serve']));
  }

  /**
   * @covers ::routes
   */
  public function testValidRoutesReturnedWithImageModule() {
    new Settings([
      'flysystem' => [
        'test' => [
          'driver' => 'local',
          'public' => TRUE,
          'config' => [
            'public' => TRUE,
            'root' => 'sites/default/files/flysystem',
          ],
        ],
      ],
    ]);

    $this->moduleHandler->moduleExists('image')->willReturn(TRUE);
    $routes = $this->router->routes();
    $this->assertCount(3, $routes);
    $this->assertTrue(isset($routes['flysystem.image_style']));
  }

}
