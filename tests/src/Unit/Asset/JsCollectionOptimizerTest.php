<?php

namespace Drupal\Tests\flysystem\Unit\Asset {

  use Drupal\Core\Asset\AssetCollectionGrouperInterface;
  use Drupal\Core\Asset\CssOptimizer;
  use Drupal\Core\Asset\JsOptimizer;
  use Drupal\Core\State\StateInterface;
  use Drupal\Tests\UnitTestCase;
  use Drupal\flysystem\Asset\AssetDumper;
  use Drupal\flysystem\Asset\CssCollectionOptimizer;
  use Drupal\flysystem\Asset\JsCollectionOptimizer;
  use Symfony\Component\DependencyInjection\ContainerBuilder;
  use org\bovigo\vfs\vfsStream;

  /**
   * @coversDefaultClass \Drupal\flysystem\Asset\JsCollectionOptimizer
   * @group flysystem
   */
  class JsCollectionOptimizerTest extends UnitTestCase {

    /**
     * {@inheritdoc}
     */
    public function setUp() {
      parent::setUp();

      if (!defined('REQUEST_TIME')) {
        define('REQUEST_TIME', time());
      }

      vfsStream::setup('flysystem');
      if (file_exists('vfs://flysystem/test.js')) {
        unlink('vfs://flysystem/test.js');
      }
    }

    /**
     * {@inheritdoc}
     */
    public function tearDown() {

      if (file_exists('vfs://flysystem/test.js')) {
        unlink('vfs://flysystem/test.js');
      }

      parent::tearDown();
    }

    /**
     * @covers \Drupal\flysystem\Asset\JsCollectionOptimizer
     * @covers \Drupal\flysystem\Asset\CssCollectionOptimizer
     */
    public function test() {
      file_put_contents('vfs://flysystem/test.js', 'asdfasdf');
      touch('vfs://flysystem/test.js', REQUEST_TIME - 1000);

      $container = new ContainerBuilder();
      $container->set('config.factory', $this->getConfigFactoryStub([
        'system.performance' => ['stale_file_threshold' => 0],
      ]));

      \Drupal::setContainer($container);

      $grouper = $this->prophesize(AssetCollectionGrouperInterface::class);
      $dumper = new AssetDumper();
      $state = $this->getMock(StateInterface::class);

      $optimizer = new JsCollectionOptimizer($grouper->reveal(), new JsOptimizer(), $dumper, $state);

      $optimizer->deleteAll();
      $this->assertFalse(file_exists('vfs://flysystem/test.js'));

      file_put_contents('vfs://flysystem/test.js', 'asdfasdf');
      touch('vfs://flysystem/test.js', REQUEST_TIME - 1000);

      $optimizer = new CssCollectionOptimizer($grouper->reveal(), new CssOptimizer(), $dumper, $state);
      $optimizer->deleteAll();
      $this->assertFalse(file_exists('vfs://flysystem/test.js'));
    }

  }
}

namespace {
  if (!function_exists('file_scan_directory')) {

    /**
     * Finds all files that match a given mask in a given directory.
     *
     * @param string $dir
     *   The base directory or URI to scan, without trailing slash.
     * @param string $mask
     *   The preg_match() regular expression for files to be included.
     * @param array $options
     *   An associative array of additional options, with the following
     *   elements:
     *   - 'nomask': The preg_match() regular expression for files to be
     *     excluded. Defaults to the 'file_scan_ignore_directories' setting.
     *   - 'callback': The callback function to call for each match. There is no
     *     default callback.
     *   - 'recurse': When TRUE, the directory scan will recurse the entire tree
     *     starting at the provided directory. Defaults to TRUE.
     *   - 'key': The key to be used for the returned associative array of
     *     files. Possible values are 'uri', for the file's URI; 'filename', for
     *     the basename of the file; and 'name' for the name of the file without
     *     the extension. Defaults to 'uri'.
     *   - 'min_depth': Minimum depth of directories to return files from.
     *     Defaults to 0.
     */
    function file_scan_directory($dir, $mask, array $options) {
      $options['callback']('vfs://flysystem/test.js');
    }

  }

  if (!function_exists('file_unmanaged_delete')) {

    /**
     * Deletes a file without database changes or hook invocations.
     *
     * This function should be used when the file to be deleted does not have an
     * entry recorded in the files table.
     *
     * @param string $uri
     *   A string containing a stream wrapper URI.
     */
    function file_unmanaged_delete($uri) {
      unlink($uri);
    }

  }
}
