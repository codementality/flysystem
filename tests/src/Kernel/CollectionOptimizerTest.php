<?php

namespace Drupal\Tests\flysystem\Kernel;

use Drupal\Core\Asset\AssetCollectionGrouperInterface;
use Drupal\Core\Asset\CssOptimizer;
use Drupal\Core\Asset\JsOptimizer;
use Drupal\Core\State\StateInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\flysystem\Asset\AssetDumper;
use Drupal\flysystem\Asset\CssCollectionOptimizer;
use Drupal\flysystem\Asset\JsCollectionOptimizer;
use org\bovigo\vfs\vfsStream;

/**
 * @covers \Drupal\flysystem\Asset\JsCollectionOptimizer
 * @covers \Drupal\flysystem\Asset\CssCollectionOptimizer
 *
 * @group flysystem
 */
class CollectionOptimizerTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->cleanUp();
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown() {
    $this->cleanUp();
    parent::tearDown();
  }

  /**
   * @covers \Drupal\flysystem\Asset\JsCollectionOptimizer
   */
  public function testJsCollectionOptimizer() {
    vfsStream::setup('js');

    $this->setSetting('flysystem', [
      'vfs' => [
        'serve_js' => TRUE,
        'driver' => 'local',
      ],
    ]);

    foreach ($this->jsFilesUnderTest() as $js_file => $expired) {
      file_put_contents($js_file, 'dummy');
      if ($expired === TRUE) {
        // 2592000 is the default value of stale_file_threshold
        touch($js_file, \Drupal::time()->getRequestTime() - 2592001);
        continue;
      }
      touch($js_file, \Drupal::time()->getRequestTime() - 2591999);
    }

    $grouper = $this->prophesize(AssetCollectionGrouperInterface::class);
    $dumper = $this->prophesize(AssetDumper::class);
    $state = $this->prophesize(StateInterface::class);

    $optimizer = new JsCollectionOptimizer($grouper->reveal(), new JsOptimizer(), $dumper->reveal(), $state->reveal(), $this->container->get('file_system'));
    $optimizer->deleteAll();

    foreach ($this->jsFilesUnderTest() as $js_file => $expired) {
      if ($expired === TRUE) {
        $this->assertFileNotExists($js_file);
        continue;
      }
      $this->assertFileExists($js_file);
    }

  }

  /**
   * @covers \Drupal\flysystem\Asset\CssCollectionOptimizer
   */
  public function testCssCollectionOptimizer() {
    vfsStream::setup('css');

    $this->setSetting('flysystem', [
      'vfs' => [
        'serve_css' => TRUE,
        'driver' => 'local',
      ],
    ]);

    foreach ($this->cssFilesUnderTest() as $css_file => $expired) {
      file_put_contents($css_file, 'dummy');
      if ($expired === TRUE) {
        // 2592000 is the default value of stale_file_threshold
        touch($css_file, \Drupal::time()->getRequestTime() - 2592001);
        continue;
      }
      touch($css_file, \Drupal::time()->getRequestTime() - 2591999);
    }

    $grouper = $this->prophesize(AssetCollectionGrouperInterface::class);
    $dumper = $this->prophesize(AssetDumper::class);
    $state = $this->prophesize(StateInterface::class);

    $optimizer = new CssCollectionOptimizer($grouper->reveal(), new CssOptimizer(), $dumper->reveal(), $state->reveal(), $this->container->get('file_system'));
    $optimizer->deleteAll();

    foreach ($this->cssFilesUnderTest() as $css_file => $expired) {
      if ($expired === TRUE) {
        $this->assertFileNotExists($css_file);
        continue;
      }
      $this->assertFileExists($css_file);
    }

  }

  /**
   * CSS files involve in testing CssCollectionOptimizer.
   *
   * @return array
   *   Keyed by the file URI, and its value is the flag of expiration. TRUE to
   *   valid, FALSE to expired.
   */
  private function cssFilesUnderTest() {
    return [
      'vfs://css/foo_expired.css' => TRUE,
      'vfs://css/bar_expired.css' => TRUE,
      'vfs://css/baz_expired.css' => TRUE,
      'vfs://css/foo.css' => FALSE,
      'vfs://css/bar.css' => FALSE,
      'vfs://css/baz.css' => FALSE,
    ];
  }

  /**
   * JS files involve in testing JsCollectionOptimizer.
   *
   * @return array
   *   Keyed by the file URI, and its value is the flag of expiration. TRUE to
   *   expired, FALSE to non-expired.
   */
  private function jsFilesUnderTest() {
    return [
      'vfs://js/foo_expired.js' => TRUE,
      'vfs://js/bar_expired.js' => TRUE,
      'vfs://js/baz_expired.js' => TRUE,
      'vfs://js/foo.js' => FALSE,
      'vfs://js/bar.js' => FALSE,
      'vfs://js/zoo.js' => FALSE,
    ];
  }

  /**
   * A helper method for removing files before and after running tests.
   */
  private function cleanUp() {
    foreach ($this->jsFilesUnderTest() as $js_file => $flag) {
      if (file_exists($js_file)) {
        unlink($js_file);
      }
    }
    foreach ($this->cssFilesUnderTest() as $css_file => $flag) {
      if (file_exists($css_file)) {
        unlink($css_file);
      }
    }
  }

}
