<?php

namespace Drupal\Tests\flysystem\Unit;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Cache\NullBackend;
use Drupal\Core\Site\Settings;
use Drupal\Core\StreamWrapper\StreamWrapperManager;
use Drupal\flysystem\Flysystem\Adapter\DrupalCacheAdapter;
use Drupal\flysystem\Flysystem\Adapter\MissingAdapter;
use Drupal\flysystem\Flysystem\Missing;
use Drupal\flysystem\FlysystemFactory;
use Drupal\flysystem\Plugin\FlysystemPluginInterface;
use Drupal\Tests\UnitTestCase;
use League\Flysystem\Adapter\NullAdapter;
use League\Flysystem\FilesystemInterface;
use League\Flysystem\Replicate\ReplicateAdapter;
use Prophecy\Argument;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @coversDefaultClass \Drupal\flysystem\FlysystemFactory
 * @group flysystem
 */
class FlysystemFactoryTest extends UnitTestCase {

  /**
   * Backend Cache.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * Event Dispatcher.
   *
   * @var \Symfony\Contracts\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Mocked File System.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy
   */
  protected $filesystem;

  /**
   * Mocked Plugin.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy
   */
  protected $plugin;

  /**
   * Mocked Plugin Manager.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy
   */
  protected $pluginManager;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->cache = new NullBackend('bin');
    $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

    $this->pluginManager = $this->prophesize(PluginManagerInterface::class);
    $this->plugin = $this->prophesize(FlysystemPluginInterface::class);
    $this->plugin->getAdapter()->willReturn(new NullAdapter());

    $this->pluginManager->createInstance('testdriver', [])->willReturn($this->plugin->reveal());
    $this->pluginManager->createInstance('', [])->willReturn(new Missing());

    $this->filesystem = $this->prophesize(StreamWrapperManager::class);
    $this->filesystem->isValidScheme(Argument::type('string'))->willReturn(TRUE);
  }

  /**
   * @covers ::getFilesystem
   * @covers ::__construct
   * @covers ::getAdapter
   * @covers ::getSettings
   * @covers ::getPlugin
   */
  public function testGetFilesystemReturnsValidFilesystem() {
    new Settings([
      'flysystem' => [
        'testscheme' => ['driver' => 'testdriver'],
      ],
    ]);

    $factory = $this->getFactory();

    $this->assertInstanceOf(FilesystemInterface::class, $factory->getFilesystem('testscheme'));
    $this->assertInstanceOf(NullAdapter::class, $factory->getFilesystem('testscheme')->getAdapter());
  }

  /**
   * @covers ::getFilesystem
   */
  public function testGetFilesystemReturnsMissingFilesystem() {
    new Settings([]);
    $factory = $this->getFactory();
    $this->assertInstanceOf(MissingAdapter::class, $factory->getFilesystem('testscheme')->getAdapter());
  }

  /**
   * @covers ::getFilesystem
   * @covers ::getAdapter
   */
  public function testGetFilesystemReturnsCachedAdapter() {
    new Settings([
      'flysystem' => [
        'testscheme' => ['driver' => 'testdriver' , 'cache' => TRUE],
      ],
    ]);

    $factory = $this->getFactory();
    $this->assertInstanceOf(DrupalCacheAdapter::class, $factory->getFilesystem('testscheme')->getAdapter());
  }

  /**
   * @covers ::getFilesystem
   * @covers ::getAdapter
   */
  public function testGetFilesystemReturnsReplicateAdapter() {
    // Test replicate.
    $this->pluginManager->createInstance('wrapped', [])->willReturn($this->plugin->reveal());

    new Settings([
      'flysystem' => [
        'testscheme' => ['driver' => 'testdriver' , 'replicate' => 'wrapped'],
        'wrapped' => ['driver' => 'testdriver'],
      ],
    ]);

    $factory = $this->getFactory();
    $this->assertInstanceOf(ReplicateAdapter::class, $factory->getFilesystem('testscheme')->getAdapter());
  }

  /**
   * @covers ::getSchemes
   * @covers ::__construct
   */
  public function testGetSchemesFiltersInvalidSchemes() {
    new Settings([
      'flysystem' => [
        'testscheme' => ['driver' => 'testdriver'],
        'invalidscheme' => ['driver' => 'testdriver'],
      ],
    ]);

    $this->filesystem->isValidScheme('invalidscheme')->willReturn(FALSE);

    $this->assertSame(['testscheme'], $this->getFactory()->getSchemes());
  }

  /**
   * @covers ::getSchemes
   */
  public function testGetSchemesHandlesNoSchemes() {
    new Settings([]);
    $this->assertSame([], $this->getFactory()->getSchemes());
  }

  /**
   * @covers ::ensure
   */
  public function testEnsureReturnsErrors() {
    new Settings([
      'flysystem' => [
        'testscheme' => ['driver' => 'testdriver'],
      ],
    ]);

    $this->plugin->ensure(FALSE)->willReturn([[
      'severity' => 'bad',
      'message' => 'Something bad',
      'context' => [],
    ],
    ]);

    $errors = $this->getFactory()->ensure();

    $this->assertSame('Something bad', $errors['testscheme'][0]['message']);
  }

  /**
   * Gets and returns the Flysystem Factory.
   *
   * @return \Drupal\flysystem\FlysystemFactory
   *   Flysystem Factory.
   */
  protected function getFactory() {
    return new FlysystemFactory(
      $this->pluginManager->reveal(),
      $this->filesystem->reveal(),
      $this->cache,
      $this->eventDispatcher
    );
  }

}
