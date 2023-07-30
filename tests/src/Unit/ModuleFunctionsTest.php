<?php

namespace Drupal\Tests\flysystem\Unit;

use Drupal\Core\File\MimeType\ExtensionMimeTypeGuesser;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\flysystem\FlysystemFactory;
use Drupal\Tests\UnitTestCase;
use org\bovigo\vfs\vfsStream;
use Prophecy\Argument;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Tests module functions.
 *
 * @group flysystem
 */
class ModuleFunctionsTest extends UnitTestCase {

  /**
   * The Flysystem factory prophecy object.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy
   */
  protected $factory;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    vfsStream::setup('module_file');

    require_once dirname(dirname(dirname(__DIR__))) . '/flysystem.module';

    $this->factory = $this->prophesize(FlysystemFactory::class);
    $this->factory->getSchemes()->willReturn(['vfs']);

    $file_system_helper = $this->prophesize(StreamWrapperManagerInterface::class);
    $file_system_helper->isValidScheme(Argument::type('string'))->will(function ($uri) {
      [$scheme] = explode('://', $uri[0]);
      return $scheme;
    });

    $guesser = $this->prophesize(ExtensionMimeTypeGuesser::class);
    $guesser->guessMimeType(Argument::type('string'))->willReturn('txt/flysystem');

    $container = new ContainerBuilder();
    $container->set('file_system', $file_system_helper->reveal());
    $container->set('flysystem_factory', $this->factory->reveal());
    $container->set('file.mime_type.guesser.extension', $guesser->reveal());

    \Drupal::setContainer($container);
  }

  /**
   * Tests flysystem_cron() calls ensure.
   */
  public function testFlysystemCronCallsEnsure() {
    $this->factory->ensure()->shouldBeCalled();
    flysystem_cron();
  }

  /**
   * Tests flysystem_rebuild() calls ensure.
   */
  public function testFlysystemRebuildCallsEnsure() {
    $this->factory->ensure()->shouldBeCalled();
    flysystem_rebuild();
  }

  /**
   * Tests flysystem_file_download() handles valid schemes.
   */
  public function testFlysystemFileDownloadFindsValidScheme() {
    $success = file_put_contents('vfs://module_file/file.txt', '1234');
    $this->assertEquals($success, 4);
    $return = flysystem_file_download('vfs://module_file/file.txt');

    $this->assertEquals(2, count($return));
    $this->assertSame('txt/flysystem', $return['Content-Type']);
    $this->assertEquals(4, $return['Content-Length']);
  }

  /**
   * Tests flysystem_file_download() ignores invalid schemes.
   */
  public function testFlysystemFileDownloadIgnoresInvalidScheme() {
    $this->assertNull(flysystem_file_download('invalid://module_file/file.txt'));
  }

}
