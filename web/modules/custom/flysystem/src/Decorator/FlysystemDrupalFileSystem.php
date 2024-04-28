<?php

namespace Drupal\flysystem\Decorator;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\File\Exception\FileException;
use Drupal\Core\File\Exception\FileWriteException;
use Drupal\Core\File\Exception\InvalidStreamWrapperException;
use Drupal\Core\File\FileExists;
use Drupal\Core\File\FileSystem;
use Drupal\Core\Site\Settings;
use Drupal\Core\StreamWrapper\StreamWrapperManager;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use League\Flysystem\FilesystemOperator;

/**
 * Decorates \Drupal::service('file_system').
 *
 * Unmodified public methods:
 * - ::basename
 * - ::getTempDirectory.
 *
 * The trait FlysystemFileSystemTrait contains the logic that is passed to the
 * Flysystem Stream Wrapper instance.
 *
 * @see Drupal\Core\File\FileSystemInteface
 * @see Drupal\Core\File\FileSystem
 */
class FlysystemDrupalFileSystem extends FileSystem {

  use FlysystemFileSystemTrait;

  /**
   * The inner service.
   *
   * @var \Drupal\Core\File\FileSystem
   */
  protected $decoratedService;

  /**
   * The site settings.
   *
   * @var \Drupal\Core\Site\Settings
   */
  protected $settings;

  /**
   * The stream wrapper manager.
   *
   * @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface
   */
  protected $streamWrapperManager;

  /**
   * Constructs a new FileSystem.
   *
   * Leverages Drupal's existing FileSystem object, enhanced by Flysystem's
   * Adapters.
   *
   * Flysystem Adapters are implemented as part of instantiating a Flysystem
   * Operator.  In this module, Flysystem Operators are registered using
   * Drupal's StreamWrapperManager.
   *
   * @param \Drupal\Core\File\FileSystem $decorated_service
   *   Drupal Core's FileSystem service, which is being decorated here.
   * @param \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $stream_wrapper_manager
   *   The stream wrapper manager.
   * @param \Drupal\Core\Site\Settings $settings
   *   The site settings.
   */
  public function __construct(FileSystem $decorated_service, StreamWrapperManagerInterface $stream_wrapper_manager, Settings $settings) {
    $this->decoratedService = $decorated_service;
    $this->streamWrapperManager = $stream_wrapper_manager;
    $this->settings = $settings;
  }

  /**
   * {@inheritdoc}
   *
   * @todo rewrite to leverage Flysystem (yes)
   * @see \Drupal\Core\File\FileSystem::moveUploadedFile()
   */
  public function moveUploadedFile($filename, $uri) {
    return $this->decoratedService->moveUploadedFile($filename, $uri);
  }

  /**
   * {@inheritdoc}
   */
  public function chmod($uri, $mode = NULL) {
    if (!isset($mode)) {
      /** @var \Drupal\Core\StreamWrapper\StreamWrapperInterface $wrapper */
      $wrapper = $this->streamWrapperManager->getViaUri($uri);
      if ($wrapper instanceof FilesystemOperator) {
        return $this->chmodFs($wrapper, $uri, $mode);
      }
    }
    return $this->decoratedService->chmod($uri, $mode);
  }

  /**
   * {@inheritdoc}
   */
  public function mkdir($uri, $mode = NULL, $recursive = FALSE, $context = NULL) {
    /** @var \Drupal\Core\StreamWrapper\StreamWrapperInterface $wrapper */
    $wrapper = $this->streamWrapperManager->getViaUri($uri);
    if ($wrapper instanceof FilesystemOperator) {
      return $this->mkdirFs($wrapper, $uri, $mode, $recursive, $context);
    }
    return $this->decoratedService->mkdir($uri, $mode, $recursive, $context);
  }

  /**
   * {@inheritdoc}
   *
   * @todo finish writing, see inline todo comments.
   */
  public function rmdir($uri, $context = NULL) {
    /** @var \Drupal\Core\StreamWrapper\StreamWrapperInterface $wrapper */
    $wrapper = $this->streamWrapperManager->getViaUri($uri);
    if ($wrapper instanceof FilesystemOperator) {
      return $this->rmdirFs($wrapper, $uri, $context);
    }
    return $this->decoratedService->rmdir($uri, $context);
  }

  /**
   * {@inheritdoc}
   */
  public function copy($source, $destination, $fileExists = FileExists::Rename) {
    /** @var \Drupal\Core\StreamWrapper\StreamWrapperInterface $wrapper */
    $wrapper = $this->streamWrapperManager->getViaScheme(StreamWrapperManager::getScheme($source));
    if ($wrapper instanceof FilesystemOperator) {
      return $this->copyFs($wrapper, $source, $destination, $fileExists);
    }
    return $this->decoratedService->copy($source, $destination, $fileExists);
  }

  /**
   * {@inheritdoc}
   */
  public function delete($path) {
    /** @var \Drupal\Core\StreamWrapper\StreamWrapperManager $wrapper */
    $wrapper = $this->streamWrapperManager->getViaUri($path);
    if ($wrapper instanceof FilesystemOperator) {
      return $this->deleteFs($wrapper, $path);
    }
    return $this->decoratedService->delete($path);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteRecursive($path, callable $callback = NULL) {
    if ($callback) {
      return $this->decoratedService->deleteRecursive($path, $callback);
    }
    /** @var \Drupal\Core\StreamWrapper\StreamWrapperInterface $wrapper */
    $wrapper = $this->streamWrapperManager->getViaUri($path);
    if ($wrapper instanceof FilesystemOperator) {
      return $this->deleteRecursiveFs($wrapper, $path, $callback);
    }
    return $this->decoratedService->deleteRecursive($path, NULL);
  }

  /**
   * {@inheritdoc}
   */
  public function move($source, $destination, $fileExists = FileExists::Rename) {
    /** @var \Drupal\Core\StreamWrapper\StreamWrapperInterface $wrapper */
    $wrapper = $this->streamWrapperManager->getViaScheme(StreamWrapperManager::getScheme($source));
    if ($wrapper instanceof FilesystemOperator) {
      return $this->moveFs($wrapper, $source, $destination, $fileExists);
    }
    return $this->decoratedService->move($source, $destination, $fileExists);
  }

  /**
   * {@inheritdoc}
   */
  public function saveData($data, $destination, $fileExists = FileExists::Rename) {
    // Write the data to a temporary file.
    // The assumption here is that temporary files will always use the local
    // filespace.
    $temp_name = $this->tempnam('temporary://', 'file');
    if (file_put_contents($temp_name, $data) === FALSE) {
      throw new FileWriteException("Temporary file '$temp_name' could not be created.");
    }
    if (!$this->streamWrapperManager->isValidUri($destination)) {
      throw new InvalidStreamWrapperException("Invalid stream wrapper: {$destination}");
    }
    /** @var \Drupal\Core\StreamWrapper\StreamWrapperInterface $wrapper */
    $wrapper = $this->streamWrapperManager->getViaScheme(StreamWrapperManager::getScheme($destination));
    if ($wrapper instanceof FilesystemOperator) {
      return $this->saveDataFs($wrapper, $temp_name, $destination, $fileExists);
    }
    return $this->decoratedService->move($temp_name, $destination, $fileExists);
  }

  /**
   * {@inheritdoc}
   */
  public function prepareDirectory(&$directory, $options = self::MODIFY_PERMISSIONS) {
    if (!$this->streamWrapperManager->isValidUri($directory)) {
      // Only trim if we're not dealing with a stream.
      $directory = rtrim($directory, '/\\');
    }
    /** @var \Drupal\Core\StreamWrapper\StreamWrapperInterface $wrapper */
    $wrapper = $this->streamWrapperManager->getViaScheme(StreamWrapperManager::getScheme($directory));
    if ($wrapper instanceof FilesystemOperator) {
      return $this->prepareDirectoryFs($wrapper, $directory, $options);
    }
    return $this->decoratedService->prepareDirectory($directory, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function createFilename($basename, $directory) {
    $original = $basename;
    // Strip control characters (ASCII value < 32). Though these are allowed in
    // some filesystems, not many applications handle them well.
    $basename = preg_replace('/[\x00-\x1F]/u', '_', $basename);
    if (preg_last_error() !== PREG_NO_ERROR) {
      throw new FileException(sprintf("Invalid filename '%s'", $original));
    }
    if (str_starts_with(PHP_OS, 'WIN')) {
      // These characters are not allowed in Windows filenames.
      $basename = str_replace([':', '*', '?', '"', '<', '>', '|'], '_', $basename);
    }

    // A URI or path may already have a trailing slash or look like "public://".
    if (str_ends_with($directory, '/')) {
      $separator = '';
    }
    else {
      $separator = '/';
    }
    $destination = $directory . $separator . $basename;

    /** @var \Drupal\Core\StreamWrapper\StreamWrapperInterface $wrapper */
    $wrapper = $this->streamWrapperManager->getViaScheme(StreamWrapperManager::getScheme($destination));
    if ($wrapper instanceof FilesystemOperator) {
      return $this->createFilenameFs($wrapper, $separator, $destination, $basename, $directory);
    }
    return $this->decoratedService->createFilename($basename, $directory);
  }

  /**
   * {@inheritdoc}
   */
  public function getDestinationFilename($destination, $fileExists) {
    $basename = $this->basename($destination);
    if (!Unicode::validateUtf8($basename)) {
      throw new FileException(sprintf("Invalid filename '%s'", $basename));
    }
    /** @var \Drupal\Core\StreamWrapper\StreamWrapperInterface $wrapper */
    $wrapper = $this->streamWrapperManager->getViaScheme(StreamWrapperManager::getScheme($destination));
    if ($wrapper instanceof FilesystemOperator) {
      return $this->getDestinationFilenameFs($wrapper, $basename, $destination, $fileExists);
    }
    return $this->decoratedService->getDestinationFilename($destination, $fileExists);
  }

}
