<?php

namespace Drupal\flysystem\FlyStream;

use Drupal\Core\StreamWrapper\PhpStreamWrapperInterface;
use Elazar\Flystream\BufferInterface;
use Elazar\Flystream\FilesystemRegistry;
use Elazar\Flystream\Lock;
use Elazar\Flystream\LockRegistryInterface;
use Elazar\Flystream\ServiceLocator;
use League\Flysystem\Config;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\UnableToWriteFile;
use League\Flysystem\UnixVisibility\VisibilityConverter;
use Psr\Log\LoggerInterface;

/**
 * Duplicates version 1.0.0 of elazar/flystream's StreamWrapper class.
 *
 * Modifications are to methods signatures, which are different than that of
 * Drupal\Core\StreamWrapper\PhpStreamWrapperInteface.
 *
 * @see https://github.com/elazar/flystream
 */
abstract class PhpStreamWrapperBase implements PhpStreamWrapperInterface {

  /**
   * File lock for writes and updates.
   *
   * @var \Elazar\Flystream\Lock|null
   *
   * @see https://flysystem.thephpleague.com/v1/docs/adapter/local/#locks
   */
  private $lock = NULL;

  /**
   * File path.
   *
   * @var string|null
   */
  private $path = NULL;

  /**
   * File permission mode.
   *
   * @var string|null
   */
  private $mode = NULL;

  /**
   * Directory listing.
   *
   * @var \Iterator|\IteratorAggregate|\Traversable|null
   */
  private $dir = NULL;

  /**
   * File resource.
   *
   * @var resource|null
   */
  private $read = NULL;

  /**
   * Stream buffer.
   *
   * @var \Elazar\Flystream\BufferInterface|\League\Flysystem\FilesystemOperator|null
   */
  private $buffer = NULL;

  /**
   * Resource context.
   *
   * @var resource
   * */
  public $context;

  // phpcs:disable Drupal.NamingConventions.ValidFunctionName.ScopeNotCamelCaps

  public function __construct() {

  }

  /**
   * {@inheritdoc}
   */
  public function dir_closedir(): bool {
    $this->log('info', __METHOD__);
    $this->dir = NULL;
    $this->path = NULL;
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function dir_opendir($path, $options): bool {
    $this->log('info', __METHOD__, func_get_args());
    try {
      $this->dir = $this->getDir($path);
      $this->path = $path;
      return TRUE;

      // @codeCoverageIgnoreStart
      // InMemoryFilesystemAdapter->listContents() returns an empty
      // array when a directory doesn't exist.
    }
    catch (\Throwable $e) {
      $this->log('error', __METHOD__, func_get_args() + [
        'exception' => $e,
      ]);
      return FALSE;
    }
    // @codeCoverageIgnoreEnd
  }

  /**
   * {@inheritdoc}
   */
  public function dir_readdir(): string|bool {
    $this->log('info', __METHOD__);
    /** @var \Iterator $dir */
    $dir = $this->dir;
    if ($dir->valid()) {
      $current = $dir->current();
      $dir->next();
      return $current->path();
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function dir_rewinddir(): bool {
    $this->log('info', __METHOD__);
    $this->dir = $this->getDir($this->path);
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function mkdir($path, $mode, $options): bool {
    $this->log('info', __METHOD__, func_get_args());
    /** @var \League\Flysystem\UnixVisibility\VisibilityConverter $visibility */
    $visibility = $this->get(VisibilityConverter::class);
    $filesystem = $this->getFilesystem($path);
    try {
      $config = $this->getConfig($path, [
        Config::OPTION_DIRECTORY_VISIBILITY =>
        $visibility->inverseForDirectory($mode),
      ]);
      $filesystem->createDirectory($path, $config);
      return TRUE;
      // @codeCoverageIgnoreStart
    }
    catch (\Throwable $e) {
      $this->log('error', __METHOD__, func_get_args() + [
        'exception' => $e,
      ]);
      return FALSE;
    }
    // @codeCoverageIgnoreEnd
  }

  /**
   * {@inheritdoc}
   */
  public function rename($path_from, $path_to): bool {
    $this->log('info', __METHOD__, func_get_args());
    $filesystem = $this->getFilesystem($path_from);
    try {
      $config = $this->getConfig($path_to);
      $filesystem->move($path_from, $path_to, $config);
      return TRUE;
    }
    catch (\Throwable $e) {
      $this->log('error', __METHOD__, func_get_args() + [
        'exception' => $e,
      ]);
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function rmdir($path, $options): bool {
    $this->log('info', __METHOD__, func_get_args());
    $filesystem = $this->getFilesystem($path);
    try {
      $filesystem->deleteDirectory($path);
      return TRUE;
      // InMemoryFilesystemAdapter->deleteDirectory() does not raise
      // an error if the target doesn't exist.
    }
    catch (\Throwable $e) {
      $this->log('error', __METHOD__, func_get_args() + [
        'exception' => $e,
      ]);
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function stream_cast($cast_as) {
    $this->log('info', __METHOD__, func_get_args());
    $this->openRead();
    return $this->read;
  }

  /**
   * {@inheritdoc}
   */
  public function stream_close(): void {
    $this->log('info', __METHOD__);
    if ($this->read !== NULL) {
      fclose($this->read);
      $this->read = NULL;
    }
    if ($this->buffer !== NULL) {
      $this->buffer->close();
      $this->buffer = NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function stream_eof(): bool {
    $this->log('info', __METHOD__);
    return feof($this->read);
  }

  /**
   * {@inheritdoc}
   */
  public function stream_flush(): bool {
    $this->log('info', __METHOD__);
    try {
      $this->buffer->flush(
            $this->getFilesystem($this->path),
            $this->path,
            $this->getConfig($this->path)
        );
    }
    catch (\Throwable $e) {
      $this->log('error', __METHOD__, func_get_args() + [
        'exception' => $e,
      ]);
      return FALSE;
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function stream_lock($operation): bool {
    $this->log('info', __METHOD__, func_get_args());
    /** @var \Elazar\Flystream\LockRegistryInterface $locks */
    $locks = $this->get(LockRegistryInterface::class);

    // For now, ignore non-blocking requests.
    $operation &= ~LOCK_NB;

    $shared = $operation === LOCK_SH;
    $exclusive = $operation === LOCK_EX;
    if ($shared || $exclusive) {
      $type = $shared
              ? Lock::TYPE_SHARED
              : Lock::TYPE_EXCLUSIVE;
      /** @var \Elazar\Flystream\Lock $lock */
      $lock = new Lock($this->path, $type);
      $result = $locks->acquire($lock);
      if ($result) {
        $this->lock = $lock;
      }
      return $result;
    }

    $result = $locks->release($this->lock);
    if ($result) {
      $this->lock = NULL;
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function stream_metadata($path, $option, $value): bool {
    $this->log('info', __METHOD__, func_get_args());
    if ($option === STREAM_META_TOUCH) {
      $filesystem = $this->getFilesystem($path);
      $config = $this->getConfig($path);
      try {
        $filesystem->write($path, '', $config);
        return TRUE;

        // @codeCoverageIgnoreStart
        // InMemoryFilesystemAdapter->write() does not raise errors
      }
      catch (\Throwable $e) {
        $this->log('error', __METHOD__, func_get_args() + [
          'exception' => $e,
        ]);
      }
      // @codeCoverageIgnoreEnd
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function stream_open($path, $mode, $options, &$opened_path = NULL): bool {
    $this->log('info', __METHOD__, func_get_args());
    $this->path = $path;
    $this->mode = $mode;
    if (strpbrk($mode, 'waxc') !== FALSE) {
      $this->stream_write('');
      $this->stream_flush();
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function stream_read($count): string {
    $this->log('info', __METHOD__, func_get_args());
    $this->openRead();
    return stream_get_contents($this->read, $count);
  }

  /**
   * {@inheritdoc}
   */
  public function stream_seek($offset, $whence = SEEK_SET): bool {
    $this->log('info', __METHOD__, func_get_args());
    $this->openRead();
    return fseek($this->read, $offset, $whence) === 0;
  }

  /**
   * {@inheritdoc}
   */
  public function stream_set_option($option, $arg1, $arg2 = NULL): bool {
    $this->log('info', __METHOD__, func_get_args());
    $this->openRead();

    if ($option === STREAM_OPTION_BLOCKING) {
      return stream_set_blocking($this->read, (bool) $arg1);
    }

    if ($option === STREAM_OPTION_READ_TIMEOUT) {
      return stream_set_timeout($this->read, $arg1, $arg2);
    }

    return stream_set_write_buffer($this->read, $arg2) === 0;
  }

  /**
   * {@inheritdoc}
   */
  public function stream_stat(): array|false {
    $this->log('info', __METHOD__);
    $this->openRead();
    return fstat($this->read);
  }

  /**
   * {@inheritdoc}
   */
  public function stream_tell(): int {
    $this->log('info', __METHOD__);
    $this->openRead();
    return (int) ftell($this->read);
  }

  /**
   * {@inheritdoc}
   */
  public function stream_truncate($new_size): bool {
    $this->log('info', __METHOD__, func_get_args());
    $this->openRead();
    return ftruncate($this->read, $new_size);
  }

  /**
   * {@inheritdoc}
   */
  public function stream_write($data): int|false {
    $this->log('info', __METHOD__, func_get_args());
    try {
      if ($this->mode === 'r') {
        throw UnableToWriteFile::atLocation(
              $this->path,
              'Stream mode is "r" which does not allow writing'
          );
      }
      if ($this->buffer === NULL) {
        $this->buffer = $this->get(BufferInterface::class);
      }
      return $this->buffer->write($data);
    }
    catch (\Throwable $e) {
      $this->log('error', __METHOD__, func_get_args() + [
        'exception' => $e,
      ]);
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function unlink($path): bool {
    $this->log('info', __METHOD__, func_get_args());
    $filesystem = $this->getFilesystem($path);
    try {
      $filesystem->delete($path);
      return TRUE;

      // @codeCoverageIgnoreStart
      // InMemoryFilesystemAdapter->delete() does not raise an error if the
      // target doesn't exist.
    }
    catch (\Throwable $e) {
      $this->log('error', __METHOD__, func_get_args() + [
        'exception' => $e,
      ]);
      return FALSE;
    }
    // @codeCoverageIgnoreEnd
  }

  /**
   * {@inheritdoc}
   */
  public function url_stat($path, $flags): array|false {
    $this->log('info', __METHOD__, func_get_args());

    $filesystem = $this->getFilesystem($path);
    /** @var \League\Flysystem\UnixVisibility\VisibilityConverter $visibility */
    $visibility = $this->get(VisibilityConverter::class);

    if (!$filesystem->fileExists($path)) {
      return FALSE;
    }

    $mode = 0100000 | $visibility->forFile(
          $filesystem->visibility($path)
      );
    $size = $filesystem->fileSize($path);
    $mtime = $filesystem->lastModified($path);

    return [
      'dev' => 0,
      'ino' => 0,
      'mode' => $mode,
      'nlink' => 0,
      'uid' => 0,
      'gid' => 0,
      'rdev' => 0,
      'size' => $size,
      'atime' => 0,
      'mtime' => $mtime,
      'ctime' => 0,
      'blksize' => 0,
      'blocks' => 0,
    ];
  }

  // phpcs:enable

  /**
   * Gets stream wrapper configuration.
   *
   * @param string $path
   *   String or directory path.
   * @param array $overrides
   *   Configuration overrides.
   *
   * @return array
   *   Array containing configuration information.
   */
  private function getConfig(string $path, array $overrides = []): array {
    $config = [];
    if ($this->context !== NULL) {
      $protocol = parse_url($path, PHP_URL_SCHEME);
      $context = stream_context_get_options($this->context);
      $config = $context[$protocol] ?? [];
    }
    return array_merge($config, $overrides);
  }

  /**
   * Get Flysystem Filesystem object.
   *
   * @param string $path
   *   File or directory path.
   *
   * @return \League\Flysystem\FilesystemOperator
   *   Flysystem Filesytem object applicable to file/directory path.
   */
  private function getFilesystem(string $path): FilesystemOperator {
    $protocol = parse_url($path, PHP_URL_SCHEME);
    /** @var \Elazar\Flystream\FilesystemRegistry $registry */
    $registry = $this->get(FilesystemRegistry::class);
    return $registry->get($protocol);
  }

  /**
   * Gets Flysystem Filesytem object associated with scheme.
   *
   * @param string $key
   *   The Filesystem scheme.
   *
   * @return \League\Flysystem\FilesystemOperator
   *   Flysystem Filesystem instance associated with scheme.
   */
  private function get(string $key) {
    return ServiceLocator::get($key);
  }

  /**
   * Gets directory contents.
   *
   * @param string $path
   *   Directory path.
   *
   * @return \Traversable
   *   Directory listing.
   */
  private function getDir(string $path): \Traversable {
    $filesystem = $this->getFilesystem($path);
    $dir = $filesystem->listContents($path, FALSE);
    // InMemoryFilesystemAdapter->listContents() only ever returns
    // a generator.
    if ($dir instanceof \Iterator) {
      return $dir;
    }
    if ($dir instanceof \IteratorAggregate) {
      return $dir->getIterator();
    }
    // @phpstan-ignore-next-line
    return new \ArrayIterator($dir);
  }

  /**
   * Opens a handle to read a Filesystem stream.
   */
  private function openRead(): void {
    if ($this->read === NULL) {
      $filesystem = $this->getFilesystem($this->path);
      $this->read = $filesystem->readStream($this->path);
    }
  }

  /**
   * Logs messages.
   *
   * @param string $level
   *   Level of message to log.
   * @param string $message
   *   Specific message text to log.
   * @param array $context
   *   Array of context options for logging.
   */
  private function log(
    string $level,
    string $message,
    array $context = [],
  ): void {
    /** @var \Psr\Log\LoggerInterface $logger */
    $logger = $this->get(LoggerInterface::class);
    $logger->log($level, $message, $context);
  }

}
