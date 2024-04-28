<?php

namespace Drupal\flysystem\Adapters;

use League\Flysystem\FilesystemAdapter;
use League\Flysystem\Config;
use League\Flysystem\FilesystemException;
use Drupal\flysystem\Adapters\MissingAdapterException;
use League\Flysystem\FileAttributes;

/**
 * An adapter used when a plugin is missing. It fails at everything.
 */
class FlysystemMissingAdapter implements FilesystemAdapter {

  /**
   * {@inheritdoc}
   */
  public function fileExists($path): bool {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function directoryExists($path): bool {
    return FALSE;
  }

  /** 
   * {@inheritdoc}
   */
  public function write(string $path, string $contents, Config $config): void {
    throw new MissingAdapterException('The Flysystem driver is missing.');
  }

  /**
   * {@inheritdoc}
   */
  public function writeStream(string $path, $contents, Config $config): void {
    throw new MissingAdapterException('The Flysystem driver is missing.');
  }

  /**
   * {@inheritdoc}
   */
  public function read(string $path): string {
    return "The Flysystem driver is missing.";
  }

  /**
   * {@inheritdoc}
   */
  public function readStream(string $path) {
    return @fopen($path, 'rb');
  }

  /**
   * {@inheritdoc}
   */
  public function delete(string $path): void {
    throw new MissingAdapterException('The Flysystem driver is missing.');
  }

  /**
   * {@inheritdoc}
   */
  public function deleteDirectory(string $path): void {
    throw new MissingAdapterException('The Flysystem driver is missing.');
  }

  /**
   * {@inheritdoc}
   */
  public function createDirectory(string $path, Config $config): void {
    throw new MissingAdapterException('The Flysystem driver is missing.');
  }

  /**
   * {@inheritdoc}
   */
  public function setVisibility(string $path, string $visibility): void {
    throw new MissingAdapterException('The Flysystem driver is missing.');
  }

  /**
   * {@inheritdoc}
   */
  public function visibility(string $path): FileAttributes {
    return new FileAttributes($path);
  }

  /**
   * {@inheritdoc}
   */
  public function mimeType(string $path): FileAttributes {
    return new FileAttributes($path);
  }

  /**
   * {@inheritdoc}
   */
  public function lastModified(string $path): FileAttributes {
    return new FileAttributes($path);
  }

  /**
   * {@inheritdoc}
   */
  public function fileSize(string $path): FileAttributes {
    return new FileAttributes($path);
  }

  /**
   * {@inheritdoc}
   */
  public function listContents(string $path, bool $deep): iterable {
    $fileAttributes[] = new FileAttributes($path);
    return $fileAttributes;
  }

  /**
   * {@inheritdoc}
   */
  public function move(string $source, string $destination, Config $config): void {
    throw new MissingAdapterException('The Flysystem driver is missing.');
  }

  /**
   * {@inheritdoc}
   */
  public function copy(string $source, string $destination, Config $config): void {
    throw new MissingAdapterException('The Flysystem driver is missing.');
  }

}
