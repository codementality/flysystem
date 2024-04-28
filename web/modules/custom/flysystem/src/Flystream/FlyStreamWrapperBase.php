<?php

namespace Drupal\flysystem\FlyStream;

use Drupal\Core\StreamWrapper\PhpStreamWrapperInterface;
use Drupal\flysystem\FlyStream\Exception\CouldNotDeleteFileException;
use Drupal\flysystem\FlyStream\Exception\CouldNotRemoveDirectoryException;
use Drupal\flysystem\FlyStream\Exception\DirectoryExistsException;
use Drupal\flysystem\FlyStream\Exception\DirectoryNotEmptyException;
use Drupal\flysystem\FlyStream\Exception\DirectoryNotFoundException;
use Drupal\flysystem\FlyStream\Exception\FileNotFoundException;
use Drupal\flysystem\FlyStream\Exception\InvalidStreamModeException;
use Drupal\flysystem\FlyStream\Exception\IsDirectoryException;
use Drupal\flysystem\FlyStream\Exception\IsNotDirectoryException;
use Drupal\flysystem\FlyStream\Exception\RootDirectoryException;
use Drupal\flysystem\FlyStream\Exception\StatFailedException;
use Drupal\flysystem\FlyStream\Exception\UnableToChangePermissionsException;
use Drupal\flysystem\FlyStream\Exception\UnableToCreateDirectoryException;
use Drupal\flysystem\FlyStream\Exception\UnableToReadException;
use Drupal\flysystem\FlyStream\Exception\UnableToWriteException;
use Elazar\Flystream\ServiceLocator;
use League\Flysystem\Config;
use League\Flysystem\FilesystemException;
use League\Flysystem\StorageAttributes;
use League\Flysystem\UnableToRetrieveMetadata;
use League\Flysystem\UnixVisibility\PortableVisibilityConverter;
use League\Flysystem\Visibility;
use League\Flysystem\WhitespacePathNormalizer;
use Psr\Log\LoggerInterface;
use Symfony\Component\Lock\Key;
use Symfony\Component\Lock\Lock;
use Symfony\Component\Lock\Store\StoreFactory;

/**
 * FlyStreamWrapper class, stream wrapper base for Flysystem Drupal module.
 */
class FlyStreamWrapperBase implements PhpStreamWrapperInterface, FlyStreamWrapperInterface {

  use ExceptionHandlerTrait;

  /**
   * Current StreamWrapper Filesystem configuration data.
   *
   * @var \Drupal\flysystem\FlyStream\FlyStreamData
   * */
  protected $current;

  /**
   * File permission mode.
   *
   * @var string|null
   */
  protected $mode = NULL;

  /**
   * Directory listing.
   *
   * @var \Iterator|\IteratorAggregate|\Traversable|null
   */
  protected $dir = NULL;

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
   */
  public $context;

  // phpcs:disable Drupal.NamingConventions.ValidFunctionName.ScopeNotCamelCaps

  public function __construct(FlyStreamData $current = NULL) {
    $this->current = $current ?? new FlyStreamData();
  }

  /**
   * {@inheritdoc}
   *
   * @todo Done
   */
  public function dir_closedir(): bool {
    unset($this->current->dirListing);
    return TRUE;
  }

  /**
   * {@inheritdoc}
   *
   * @todo Done
   */
  public function dir_opendir($path, $options): bool {

    $this->current->setPath($path);
    try {
      $listing = $this->current->filesystem->listContents($this->current->file)->getIterator();
      $this->current->dirListing = ($listing instanceof \Iterator) ? $listing : new \IteratorIterator($listing);
    }
    catch (FilesystemException $e) {
      return $this->triggerError(
            DirectoryNotFoundException::atLocation(__METHOD__, $path, $e)
            );
    }

    $valid = @is_dir($path);
    if (!$valid) {
      return self::triggerError(
            DirectoryNotFoundException::atLocation(__METHOD__, $path)
        );
    }

    return TRUE;

  }

  /**
   * {@inheritdoc}
   *
   * @todo Done
   */
  public function dir_readdir(): string|bool {

    if (!$this->current->dirListing->valid()) {
      return FALSE;
    }

    $item = $this->current->dirListing->current();

    $this->current->dirListing->next();

    return basename($item->path());

  }

  /**
   * {@inheritdoc}
   *
   * @todo Done
   */
  public function dir_rewinddir(): bool {

    try {
      $listing = $this->current->filesystem->listContents($this->current->file)->getIterator();
      $this->current->dirListing = ($listing instanceof \Iterator) ? $listing : new \IteratorIterator($listing);
    }
    catch (FilesystemException $e) {
      return self::triggerError($e);
    }

    return TRUE;

  }

  /**
   * {@inheritdoc}
   *
   * @todo Done
   */
  public function mkdir($path, $mode, $options): bool {

    if (file_exists($path)) {
      return self::triggerError(DirectoryExistsException::atLocation(__METHOD__, $path));
    }

    $this->current->setPath($path);

    try {
      $visibility = new PortableVisibilityConverter();
      $config = [
        Config::OPTION_VISIBILITY => $visibility->inverseForDirectory($mode),
      ];
      $this->current->filesystem->createDirectory($this->current->file, $config);

      return TRUE;
    }
    catch (FilesystemException $e) {
      return self::triggerError(
            UnableToCreateDirectoryException::atLocation(__METHOD__, $path, $e)
            );
    }
  }

  /**
   * {@inheritdoc}
   *
   * @todo Done
   */
  public function rename($path_from, $path_to): bool {

    $this->current->setPath($path_from);

    $errorLocation = $path_from . ',' . $path_to;
    if (!file_exists($path_from)) {
      return self::triggerError(FileNotFoundException::atLocation(__METHOD__, $errorLocation));
    }

    if (file_exists($path_to)) {
      if (is_file($path_from) && is_dir($path_to)) {
        return self::triggerError(
              IsDirectoryException::atLocation(__METHOD__, $errorLocation)
          );
      }
      if (is_dir($path_from) && is_file($path_to)) {
        return self::triggerError(
              IsNotDirectoryException::atLocation(__METHOD__, $errorLocation)
          );
      }
    }

    try {
      $this->current->filesystem->move($this->current->file, FlyStreamData::getFile($path_to));

      return TRUE;
    }
    catch (FilesystemException $e) {
      return self::triggerError(
            DirectoryNotEmptyException::atLocation(__METHOD__, $errorLocation, $e)
            );
    }

  }

  /**
   * {@inheritdoc}
   *
   * @todo Done
   */
  public function rmdir($path, $options): bool {

    $this->current->setPath($path);

    $n = new WhitespacePathNormalizer();
    $n->normalizePath($this->current->file);
    if ('' === $n->normalizePath($this->current->file)) {
      return self::triggerError(
            RootDirectoryException::atLocation(__METHOD__, $this->current->path)
        );
    }

    if (($options & STREAM_MKDIR_RECURSIVE) !== 0) {
      try {
        $this->current->filesystem->deleteDirectory($this->current->file);
        return TRUE;
      }
      catch (FilesystemException $e) {
        return self::triggerError(
              CouldNotRemoveDirectoryException::atLocation(__METHOD__, $this->current->path, $e)
                );
      }
    }

    try {
      $listing = $this->current->filesystem->listContents($this->current->file);
    }
    catch (FilesystemException $e) {
      return self::triggerError(
            DirectoryNotEmptyException::atLocation(__METHOD__, $this->current->path)
            );
    }

    foreach ($listing as $ignored) {
      if (!$ignored instanceof StorageAttributes) {
        continue;
      }

      return self::triggerError(
            DirectoryNotEmptyException::atLocation(__METHOD__, $this->current->path)
        );
    }

    try {
      $this->current->filesystem->deleteDirectory($this->current->file);
      return TRUE;
    }
    catch (FilesystemException $e) {
      return self::triggerError(
            CouldNotRemoveDirectoryException::atLocation(__METHOD__, $this->current->path, $e)
            );
    }
  }

  /**
   * {@inheritdoc}
   *
   * @todo Done
   */
  public function stream_cast($cast_as) {
    return $this->current->handle;
  }

  /**
   * {@inheritdoc}
   *
   * @todo Done
   */
  public function stream_close(): void {

    if (!is_resource($this->current->handle)) {
      return;
    }

    if ($this->current->workOnLocalCopy) {
      fflush($this->current->handle);
      rewind($this->current->handle);

      try {
        $this->current->filesystem->writeStream($this->current->file, $this->current->handle);
      }
      catch (FilesystemException $e) {
        trigger_error(
          'stream_close(' . $this->current->path . ') Unable to sync file : ' . $e->getMessage(),
          E_USER_WARNING
              );
      }
    }

    fclose($this->current->handle);
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
   *
   * @todo Done
   */
  public function stream_eof(): bool {
    if (!is_resource($this->current->handle)) {
      return FALSE;
    }

    return feof($this->current->handle);
  }

  /**
   * {@inheritdoc}
   *
   * @todo Done
   */
  public function stream_flush(): bool {

    if (!is_resource($this->current->handle)) {
      trigger_error(
        'stream_flush(): Supplied resource is not a valid stream resource',
        E_USER_WARNING
      );

      return FALSE;
    }

    $success = fflush($this->current->handle);

    if ($this->current->workOnLocalCopy) {
      fflush($this->current->handle);
      $currentPosition = ftell($this->current->handle);
      rewind($this->current->handle);

      try {
        $this->current->filesystem->writeStream($this->current->file, $this->current->handle);
      }
      catch (FilesystemException $e) {
        trigger_error(
          'stream_flush(' . $this->current->path . ') Unable to sync file : ' . $e->getMessage(),
          E_USER_WARNING
        );
        $success = FALSE;
      }

      if (FALSE !== $currentPosition) {
        if (is_resource($this->current->handle)) {
          fseek($this->current->handle, $currentPosition);
        }
      }
    }

    $this->current->bytesWritten = 0;

    return $success;
  }

  /**
   * {@inheritdoc}
   *
   * @todo In progress
   */
  public function stream_lock($operation): bool {
    if (NULL === $this->current->lockKey) {
      $this->current->lockKey = new Key($this->current->path);
    }

    $store = StoreFactory::createStore((string) $this->current->config[FlyStreamWrapperInterface::LOCK_STORE]);
    $lock = new Lock(
        $this->current->lockKey,
        $store,
        (float) $this->current->config[FlyStreamWrapperInterface::LOCK_TTL],
        FALSE
    );

    switch ($operation) {
      case LOCK_SH:
        return $lock->acquireRead(TRUE);

      case LOCK_EX:
        return $lock->acquire(TRUE);

      case LOCK_UN:
        $lock->release();
        return TRUE;

      case LOCK_SH | LOCK_NB:
        return $lock->acquireRead();

      case LOCK_EX | LOCK_NB:
        return $lock->acquire();
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   *
   * @todo Done
   */
  public function stream_metadata($path, $option, $value): bool {
    $this->current->setPath($path);
    $filesystem = $this->current->filesystem;
    $file = $this->current->file;

    switch ($option) {
      case STREAM_META_ACCESS:
        if (!is_int($value)) {
          $value = (int) $value;
        }
        $converter = new PortableVisibilityConverter();
        $visibility = is_dir($path) ? $converter->inverseForDirectory($value) : $converter->inverseForFile($value);

        try {
          $filesystem->setVisibility($file, $visibility);
        }
        catch (FilesystemException $e) {
          if (!$this->current->ignoreVisibilityErrors()) {
            return self::triggerError(UnableToChangePermissionsException::atLocation(
              __METHOD__,
              $this->current->path,
              decoct($value),
              $e
            ));
          }
        }
        return TRUE;

      case STREAM_META_TOUCH:
        try {
          if (!$filesystem->fileExists($file)) {
            $filesystem->write($file, '');
          }
        }
        catch (FilesystemException $e) {
          return self::triggerError(UnableToWriteException::atLocation(
          __METHOD__,
          $this->current->path,
          $e
          ));
        }

        return TRUE;

      default:
        return FALSE;
    }

  }

  /**
   * {@inheritdoc}
   *
   * @todo Done
   */
  public function stream_open($path, $mode, $options, &$openedPath = NULL): bool {
    $this->current->setPath($path);
    $filesystem = $this->current->filesystem;
    $file = $this->current->file;

    if (!preg_match('/^[rwacx](\+b?|b\+?)?$/', $mode)) {
      return self::triggerError(InvalidStreamModeException::atLocation(
        __METHOD__,
        $this->current->path,
        $mode
      ));
    }

    $this->current->writeOnly = !strpos($mode, '+');
    try {
      if ('r' === $mode[0] && $this->current->writeOnly) {
        $this->current->handle = $filesystem->readStream($file);
        $this->current->workOnLocalCopy = FALSE;
        $this->current->writeOnly = FALSE;
      }
      else {
        $this->current->handle = fopen('php://temp', 'w+b');
        $this->current->workOnLocalCopy = TRUE;

        if ('w' !== $mode[0] && $filesystem->fileExists($file)) {
          if ('x' === $mode[0]) {
            throw UnableToWriteException::atLocation(__METHOD__, $this->current->path);
          }

          $result = FALSE;
          if (is_resource($this->current->handle)) {
            $result = stream_copy_to_stream($filesystem->readStream($file), $this->current->handle);
          }
          if (!$result) {
            throw UnableToWriteException::atLocation(__METHOD__, $this->current->path);
          }
        }
      }

      $this->current->alwaysAppend = 'a' === $mode[0];
      if (is_resource($this->current->handle) && !$this->current->alwaysAppend) {
        @rewind($this->current->handle);
      }
    }
    catch (FilesystemException $e) {
      if (($options & STREAM_REPORT_ERRORS) !== 0) {
        return self::triggerError(UnableToReadException::atLocation(
          __METHOD__,
          $this->current->path,
          $e
        ));
      }
      return FALSE;
    }

    if ($this->current->handle && $options & STREAM_USE_PATH) {
      $openedPath = $path;
    }

    if (is_resource($this->current->handle)) {
      return TRUE;
    }

    if (($options & STREAM_REPORT_ERRORS) !== 0) {
      return self::triggerError(FileNotFoundException::atLocation(__METHOD__, $this->current->path));
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   *
   * @todo DONE
   */
  public function stream_read($count): string {

    if ($this->current->writeOnly || !is_resource($this->current->handle) || $count < 0) {
      return '';
    }

    return (string) fread($this->current->handle, $count);
  }

  /**
   * {@inheritdoc}
   *
   * @todo Done
   */
  public function stream_seek($offset, $whence = SEEK_SET): bool {
    if (!is_resource($this->current->handle)) {
      return FALSE;
    }

    return 0 === fseek($this->current->handle, $offset, $whence);
  }

  /**
   * {@inheritdoc}
   *
   * @todo Done
   */
  public function stream_set_option($option, $arg1, $arg2 = NULL): bool {
    if (!is_resource($this->current->handle)) {
      return FALSE;
    }

    switch ($option) {
      case STREAM_OPTION_BLOCKING:
        return stream_set_blocking($this->current->handle, 1 === $arg1);

      case STREAM_OPTION_READ_BUFFER:
        return 0 === stream_set_read_buffer(
          $this->current->handle,
            STREAM_BUFFER_NONE === $arg1 ? 0 : (int) $arg2
          );

      case STREAM_OPTION_WRITE_BUFFER:
        $this->current->writeBufferSize = STREAM_BUFFER_NONE === $arg1 ? 0 : (int) $arg2;

        return TRUE;

      case STREAM_OPTION_READ_TIMEOUT:
        return stream_set_timeout($this->current->handle, $arg1, (int) $arg2);
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   *
   * @todo Done
   */
  public function stream_stat(): array|false {
    try {
      return $this->getStat($this->current);
    }
    catch (FilesystemException $e) {
      self::triggerError(StatFailedException::atLocation(__METHOD__, $this->current->path, $e));

      return FALSE;
    }

  }

  /**
   * {@inheritdoc}
   *
   * @todo Done
   */
  public function stream_tell(): int {
    if (!is_resource($this->current->handle)) {
      return 0;
    }

    if ($this->current->alwaysAppend && $this->current->writeOnly) {
      return 0;
    }

    return (int) ftell($this->current->handle);
  }

  /**
   * {@inheritdoc}
   *
   * @todo Done
   */
  public function stream_truncate($new_size): bool {
    if (!is_resource($this->current->handle) || $new_size < 0) {
      return FALSE;
    }

    return ftruncate($this->current->handle, $new_size);
  }

  /**
   * {@inheritdoc}
   *
   * @todo Done
   */
  public function stream_write($data): int|false {

    if (!is_resource($this->current->handle)) {
      return 0;
    }

    if ($this->current->alwaysAppend) {
      fseek($this->current->handle, 0, SEEK_END);
    }

    $size = (int) fwrite($this->current->handle, $data);
    $this->current->bytesWritten += $size;

    if ($this->current->alwaysAppend) {
      fseek($this->current->handle, 0, SEEK_SET);
    }

    return $size;
  }

  /**
   * {@inheritdoc}
   *
   * @todo done
   */
  public function unlink($path): bool {

    $this->current->setPath($path);

    if (!file_exists($this->current->path)) {
      return self::triggerError(FileNotFoundException::atLocation(__METHOD__, $this->current->path));
    }

    try {
      $this->current->filesystem->delete($this->current->file);
      return TRUE;
    }
    catch (FilesystemException $e) {
      return self::triggerError(
        CouldNotDeleteFileException::atLocation(__METHOD__, $this->current->path, $e)
          );
    }
  }

  /**
   * {@inheritdoc}
   */
  public function url_stat($path, $flags): array|false {
    $this->current->setPath($path);

    try {
      return $this->getStat($this->current);
    }
    catch (FilesystemException $e) {
      if (($flags & STREAM_URL_STAT_QUIET) !== 0) {
        return FALSE;
      }

      self::triggerError(StatFailedException::atLocation(__METHOD__, $path, $e));

      return FALSE;
    }
  }

  // phpcs:enable

  /**
   * Get file, directory, or resource stat.
   *
   * @return array<int|string,int|string>|false
   *   Requested stat, or FALSE if it doesn't exist.
   *
   * @throws \League\Flysystem\FilesystemException
   */
  protected function getStat(FlyStreamData $current) {
    $stats = [];

    if ($current->workOnLocalCopy && is_resource($current->handle)) {
      $stats = fstat($current->handle);
      if (!$stats) {
        return FALSE;
      }
      if ($current->filesystem->fileExists($current->file)) {
        [$mode, $size, $time] = $this->getRemoteStats($current);

        unset($size);
      }
    }
    else {
      [$mode, $size, $time] = $this->getRemoteStats($current);
    }

    foreach (self::STATS_ZERO as $key) {
      $stats[$key] = 0;
    }

    foreach (self::STATS_MINUS_ONE as $key) {
      $stats[$key] = -1;
    }

    if (isset($mode)) {
      foreach (self::STATS_MODE as $key) {
        $stats[$key] = $mode;
      }
    }

    if (isset($size)) {
      foreach (self::STATS_SIZE as $key) {
        $stats[$key] = $size;
      }
    }

    if (isset($time)) {
      foreach (self::STATS_TIME as $key) {
        $stats[$key] = $time;
      }
    }

    $stats['uid'] = $stats[4] = (int) $current->config[FlyStreamWrapperInterface::UID];
    $stats['gid'] = $stats[5] = (int) $current->config[FlyStreamWrapperInterface::GID];

    return $stats;
  }

  /**
   * Gets stats from remote resource.
   *
   * @throws \League\Flysystem\FilesystemException
   *
   * @return array<int,int>
   *   Requested stats.
   */
  protected function getRemoteStats(FlyStreamData $current): array {
    $converter = new PortableVisibilityConverter(
      (int) $current->config[FlyStreamWrapperInterface::VISIBILITY_FILE_PUBLIC],
      (int) $current->config[FlyStreamWrapperInterface::VISIBILITY_FILE_PRIVATE],
      (int) $current->config[FlyStreamWrapperInterface::VISIBILITY_DIRECTORY_PUBLIC],
      (int) $current->config[FlyStreamWrapperInterface::VISIBILITY_DIRECTORY_PRIVATE],
      (string) $current->config[FlyStreamWrapperInterface::VISIBILITY_DEFAULT_FOR_DIRECTORIES]
    );

    try {
      $visibility = $current->filesystem->visibility($current->file);
    }
    catch (UnableToRetrieveMetadata | \TypeError $e) {
      if (!$current->ignoreVisibilityErrors()) {
        throw $e;
      }

      $visibility = Visibility::PUBLIC;
    }

    $mode = 0;
    $size = 0;
    $lastModified = 0;

    try {
      if ('directory' === $current->filesystem->mimeType($current->file)) {
        [$mode, $size, $lastModified] = $this->getRemoteDirectoryStats($current, $converter, $visibility);
      }
      else {
        [$mode, $size, $lastModified] = $this->getRemoteFileStats($current, $converter, $visibility);
      }
    }
    catch (UnableToRetrieveMetadata $e) {
      if (method_exists($current->filesystem, 'directoryExists')) {
        if ($current->filesystem->directoryExists($current->file)) {
          [$mode, $size, $lastModified] = $this->getRemoteDirectoryStats($current, $converter, $visibility);
        }
        elseif ($current->filesystem->fileExists($current->file)) {
          [$mode, $size, $lastModified] = $this->getRemoteFileStats($current, $converter, $visibility);
        }
      }
      else {
        throw $e;
      }
    }

    return [$mode, $size, $lastModified];
  }

  /**
   * Get stats for Remote Directory.
   *
   * @return array<int, int>
   *   Requested stats.
   *
   * @throws \League\Flysystem\FilesystemException
   */
  private function getRemoteDirectoryStats(
    FlyStreamData $current,
    PortableVisibilityConverter $converter,
    string $visibility,
  ): array {
    $mode = 040000 + $converter->forDirectory($visibility);
    $size = 0;
    $lastModified = $this->getRemoteDirectoryLastModified($current);
    return [$mode, $size, $lastModified];
  }

  /**
   * Get stats for remote file.
   *
   * @return array<int, int>
   *   Requested stats.
   *
   * @throws \League\Flysystem\FilesystemException
   */
  private function getRemoteFileStats(
    FlyStreamData $current,
    PortableVisibilityConverter $converter,
    string $visibility,
  ): array {
    $mode = 0100000 + $converter->forFile($visibility);
    $size = $current->filesystem->fileSize($current->file);
    $lastModified = $current->filesystem->lastModified($current->file);
    return [$mode, $size, $lastModified];
  }

  /**
   * Gets Last Modified stat for remote directory.
   *
   * @param FlyStreamData $current
   *   Current resource data object.
   *
   * @return int
   *   Last modified stat.
   *
   * @throws \League\Flysystem\FilesystemException
   */
  private function getRemoteDirectoryLastModified(FlyStreamData $current): int {
    if (!$current->emulateDirectoryLastModified()) {
      return $current->filesystem->lastModified($current->file);
    }

    $lastModified = 0;
    $listing = $current->filesystem->listContents($current->file)->getIterator();
    $dirListing = $listing instanceof \Iterator ? $listing : new \IteratorIterator($listing);

    /** @var \League\Flysystem\FileAttributes $item */
    foreach ($dirListing as $item) {
      $lastModified = max($lastModified, $item->lastModified());
    }
    return $lastModified;
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
