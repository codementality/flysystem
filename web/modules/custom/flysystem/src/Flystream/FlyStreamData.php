<?php

namespace Drupal\flysystem\FlyStream;

/**
 * FlyStream Stream Wrapper Configuration data.
 */
class FlyStreamData {
  /**
   * File directory path.
   *
   * @var string
   */
  public $path;

  /**
   * Wrapper schema.
   *
   * @var string
   */
  public $protocol;

  /**
   * File path.
   *
   * @var string
   */
  public $file;

  /**
   * Flysystem Filesystem instance.
   *
   * @var \League\Flysystem\FilesystemOperator
   */
  public $filesystem;

  /**
   * Configuration data.
   *
   * @var array<string, int|string|bool|null>
   */
  public $config = [];

  /**
   * Resource handle.
   *
   * @var resource|false
   */
  public $handle = FALSE;

  /**
   * Write only setting.
   *
   * @var bool
   */
  public $writeOnly = FALSE;

  /**
   * Always Append setting.
   *
   * @var bool
   */
  public $alwaysAppend = FALSE;

  /**
   * Work on Local Copy setting.
   *
   * @var bool
   */
  public $workOnLocalCopy = FALSE;

  /**
   * Write Buffer Size.
   *
   * @var int
   */
  public $writeBufferSize = 0;

  /**
   * Bytes written to stream.
   *
   * @var int
   */
  public $bytesWritten = 0;

  /**
   * Lock Key setting.
   *
   * @var \Symfony\Component\Lock\Key
   */
  public $lockKey;

  /**
   * Directory Listing contents.
   *
   * @var \Iterator<mixed,\League\Flysystem\StorageAttributes>
   */
  public $dirListing;

  /**
   * Configures settings based on path.
   *
   * @param string $path
   *   File path.
   */
  public function setPath(string $path): void {
    $this->path = $path;
    $this->protocol = substr($path, 0, (int) strpos($path, '://'));
    $this->file = self::getFile($path);
    $this->filesystem = FlyStreamWrapper::$filesystems[$this->protocol];
    $this->config = FlyStreamWrapper::$config[$this->protocol];
  }

  /**
   * Get the file path.
   *
   * @param string $path
   *   File path.
   *
   * @return string
   *   File path converted.
   */
  public static function getFile(string $path): string {
    return (string) substr($path, strpos($path, '://') + 3);
  }

  /**
   * Gets Ignore Visibility Errors settings.
   *
   * @return bool
   *   Boolean setting.
   */
  public function ignoreVisibilityErrors(): bool {
    return (bool) $this->config[FlyStreamWrapper::IGNORE_VISIBILITY_ERRORS];
  }

  /**
   * Gets Emulate Directory Last Modified settings.
   *
   * @return bool
   *   Boolean setting.
   */
  public function emulateDirectoryLastModified(): bool {
    return (bool) $this->config[FlyStreamWrapper::EMULATE_DIRECTORY_LAST_MODIFIED];
  }

}
