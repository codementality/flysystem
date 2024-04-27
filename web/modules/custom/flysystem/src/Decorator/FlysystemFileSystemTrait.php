<?php

namespace Drupal\flysystem\Decorator;

use Drupal\Core\File\Exception\NotRegularFileException;
use Drupal\Core\File\FileExists;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\Core\Site\Settings;
use League\Flysystem\FilesystemOperator;

/**
 * Contains all logic to convert Drupal's FileSystem service methods to utilize
 * League\Flysystem\Filesystem methods and logic.
 *
 * The purpose of this trait is to isolate the logic specific to this logic
 * conversion into one maintainable class, separate from the decorator logic
 * itself, which is pretty straightforward.
 */
trait FlysystemFileSystemTrait {

  /**
   * Converts Drupal's FileSystem::chmod() method to leverage
   * League\Flysystem\FilesystemOperator methods.
   *
   * @param \League\Flysystem\FilesystemOperator $wrapper
   *   Drupal StreamWrapper service.
   * @param string $uri
   *   A string containing a URI file, or directory path
   * @param int $mode
   *   Integer value for permissions.
   *
   * @return bool
   *   TRUE for success, FALSE in the event of an error.
   */
  protected function chmodFs($wrapper, $uri, $mode = NULL): bool {
    try {
      $drupalMode = NULL;
      if ($wrapper->directoryExists($uri)) {
        // @todo May have to write a Flysystem VisibilityConverter for
        // Local file adapter that substitutes Drupal file and directory
        // permission settings for the default settings in Flysystem.
        $drupalMode = $this->settings->get('file_chmod_directory', static::CHMOD_DIRECTORY);
      }
      elseif ($wrapper->fileExists($uri)) {
        $drupalMode = $this->settings->get('file_chmod_file', static::CHMOD_FILE);
      }
      $wrapper->setVisibility($uri, $drupalMode);
      return TRUE;
    }
    catch (\Exception $e) {
      // @todo write exception handler for possible exceptions thrown
      // by FlysystemAdapter::setVisibility();
    }
    return FALSE;
  }

  /**
   * Converts Drupal's FileSystem::mkdir() method to leverage
   * League\Flysystem\FilesystemOperator methods.
   *
   * @param \League\Flysystem\FilesystemOperator $wrapper
   *   Drupal StreamWrapper service.
   * @param string $uri
   *   A URI or pathname.
   * @param int $mode
   *   Mode given to created directories. Defaults to the directory mode
   *   configured in the Drupal installation. It must have a leading zero.
   * @param bool $recursive
   *   Create directories recursively, defaults to FALSE. Cannot work with a
   *   mode which denies writing or execution to the owner of the process.
   * @param resource $context
   *   Refer to http://php.net/manual/ref.stream.php
   *
   * @return bool
   *   Boolean TRUE on success, or FALSE on failure.
   *
   * @todo finish writing, see inline todo comments.
   */
  protected function mkdirFs($wrapper, $uri, $mode = NULL, $recursive = FALSE, $context = NULL): bool {
    try {
      // @todo figure out how to utilize directory permissions, see notes on
      // Flysystem VisibilityConverter.
      // @see \League\Flysystem\Filesystem::createDirectory()
      $wrapper->createDirectory($uri);
      return TRUE;
    }
    catch (\Exception $e) {
      // @todo write exception handler to handle Flysystem Exception, and
      // return a proper return code, which is FALSE.
    }
    return FALSE;
  }

  /**
   * Converts Drupal's FileSystem::rmdir() method to leverage
   * League\Flysystem\FilesystemOperator methods.
   *
   * @param \League\Flysystem\FilesystemOperator $wrapper
   *   Drupal StreamWrapper service.
   * @param string $uri
   *   A URI or pathname.
   * @param resource $context
   *   Refer to http://php.net/manual/ref.stream.php
   *
   * @return bool
   *   Boolean TRUE on success, or FALSE on failure.
   *
   * @todo finish writing, see inline todo comments.
   */
  protected function rmdirFs($wrapper, $uri, $context = NULL): bool {
    try {
      // @todo figure out how to utilize directory permissions, see notes on
      // Flysystem VisibilityConverter.
      // @see \League\Flysystem\Filesystem::deleteDirectory()
      $wrapper->deleteDirectory($uri);
      return TRUE;
    }
    catch (\Exception $e) {
      // @todo write exception handler to handle Flysystem Exception, and
      // return a proper return code, which is FALSE.
    }
    return FALSE;
  }

  /**
   * Converts Drupal's FileSystem::copy() method to leverage
   * League\Flysystem\FilesystemOperator methods.
   *
   * @param \League\Flysystem\FilesystemOperator $wrapper
   *   Drupal StreamWrapper service.
   * @param string $source
   *   A string specifying the filepath or URI of the source file.
   * @param string $destination
   *   A URI containing the destination that $source should be copied to. The
   *   URI may be a bare filepath (without a scheme).
   * @param \Drupal\Core\File\FileExists|int $fileExists
   *   The behavior when the destination file already exists.
   *
   * @return string
   *   The path to the new file.
   * 
   * @todo Rewrite to leverage Flysystem (yes)
   */
  protected function copyFs($wrapper, $source, $destination, $fileExists): string  {
    try {
      // @todo figure out how to utilize directory permissions, see notes on
      // Flysystem VisibilityConverter.
      // @see \League\Flysystem\Filesystem::copy().
      $wrapper->copy($source, $destination);
      return $destination;
    }
    catch (\Exception $e) {
      // @todo write exception handler to handle Flysystem Exception, and
      // return a proper return code, which is FALSE.
    }
    return '';
  }

  /**
   * Converts Drupal's FileSystem::delete() method to leverage
   * League\Flysystem\FilesystemOperator methods.
   *
   * @param \League\Flysystem\FilesystemOperator $wrapper
   *   Drupal StreamWrapper service.
   * @param string $path
   *   A string containing a file path or (streamwrapper) URI.
   *
   * @return TRUE
   *   Always return true, unless an exception is thrown.
   *
   * @todo finish writing, see inline todo comments.
   */
  protected function deleteFs($wrapper, $path) {
    if ($wrapper->directoryExists($path)) {
      throw new NotRegularFileException("Cannot delete '$path' because it is a directory. Use deleteRecursive() instead.");
    }
    if ($wrapper->fileExists($path)) {
      try {   
        $wrapper->delete($path);
      }
      catch (\Exception $e) {
        // @todo modify \Exception to use class from Flysystem,
        // and throw a proper exception.
      }
    }
    // If file does not exist, return TRUE, that is the intended result.
    return TRUE;
  }

  /**
   * Converts Drupal's FileSystem::deleteRecursive() method to leverage
   * League\Flysystem\FilesystemOperator methods.
   *
   * @param \League\Flysystem\FilesystemOperator $wrapper
   *   Drupal StreamWrapper service.
   * @param string $path
   *   A string containing either an URI or a file or directory path.
   * @param callable|null $callback
   *   Callback function to run on each file prior to deleting it and on each
   *   directory prior to traversing it. For example, can be used to modify
   *   permissions.
   *
   * @return bool
   *   TRUE if successful, FALSE if not.
   */
  protected function deleteRecursiveFs($wrapper, $path, callable $callback = NULL):  bool {
    if (!$wrapper->fileExists($path)) {
      return TRUE;
    }

    if ($wrapper->directoryExists($path)) {
      $dir = $wrapper->listContents($path);
      foreach ($dir as $entry) {
        $entry_path = $path . '/' . $entry->path();
        $this->deleteRecursive($entry_path, $callback);
      }
      return $this->rmdir($path);
    }
    return $this->delete($path);
  }

  /**
   * Converts Drupal's FileSystem::move() method to leverage
   * League\Flysystem\FilesystemOperator methods.
   *
   * @param \League\Flysystem\FilesystemOperator $wrapper
   *   Drupal StreamWrapper service.
   * @param string $source
   *   A string specifying the filepath or URI of the source file.
   * @param string $destination
   *   A URI containing the destination that $source should be moved to. The
   *   URI may be a bare filepath (without a scheme) and in that case the
   *   default scheme (public://) will be used.
   * @param \Drupal\Core\File\FileExists|int $fileExists
   *   Replace behavior when the destination file already exists.
   * 
   * @return string
   *   The path to the new file.
   *
   * @todo finish writing, see inline todo comments.
   */
  protected function moveFs($wrapper, $source, $destination, $fileExists): string {
    try {
      // @todo figure out how to utilize directory permissions, see notes on
      // Flysystem VisibilityConverter.
      // @see \League\Flysystem\Filesystem::move().
      // What do we do with the $replace flag?
      $wrapper->move($source, $destination);
      return $destination;
    }
    catch (\Exception $e) {
      // @todo write exception handler to handle Flysystem Exception, and
      // return a proper return code, which is FALSE.
    }
    return '';
  }

  /**
   * Converts Drupal's FileSystem::saveData() method to leverage
   * League\Flysystem\FilesystemOperator methods.
   *
   * @param \League\Flysystem\FilesystemOperator $wrapper
   *   Drupal StreamWrapper service.
   * @param string $temp_nam
   *   A string containing the contents of the file, moved to a temporary
   *   location.
   * @param string $destination
   *   A string containing the destination location. This must be a stream
   *   wrapper URI.
   * @param \Drupal\Core\File\FileExists|int $fileExists
   *   Replace behavior when the destination file already exists.
   *
   * @return string
   *   A string with the path of the resulting file, or FALSE on error.
   *
   * @todo finish writing, see inline todo comments.
   */
  protected function saveDataFs($wrapper, $temp_nam, $destination, $fileExists) {
    // Move the file to its final destination.
    try {
      // @todo figure out how to utilize directory permissions, see notes on
      // Flysystem VisibilityConverter.
      // @see \League\Flysystem\Filesystem::move().
      // Do we need to use the $replace flag here in the FlysystemAdapter?
      $wrapper->move($temp_nam, $destination);
      return $destination;
    }
    catch (\Exception $e) {
      // @todo write exception handler to handle Flysystem Exception, and
      // return a proper return code, which is FALSE.
    }
    return '';
  }

  /**
   * Converts Drupal's FileSystem::prepareDirectory() method to leverage
   * League\Flysystem\FilesystemOperator methods.
   *
   * @param \League\Flysystem\FilesystemOperator $wrapper
   *   Drupal StreamWrapper service.
   * @param string $directory
   *   A string reference containing the name of a directory path or URI. A
   *   trailing slash will be trimmed from a path.
   * @param int $options
   *   A bitmask to indicate if the directory should be created if it does
   *   not exist (FileSystemInterface::CREATE_DIRECTORY) or made writable if it
   *   is read-only (FileSystemInterface::MODIFY_PERMISSIONS).
   *
   * @return bool
   *   TRUE if the directory exists (or was created) and is writable. FALSE
   *   otherwise.
   *
   * @todo finish writing, see inline todo comments.
   * @see \League\Flysystem\FilesystemOperator::directoryExists()
   */
  protected function prepareDirectoryFs($wrapper, &$directory, $options): bool {
    // How do we use the flags, self::MODIFY_PERMISSIONS and static::CREATE_DIRECTORY here?
    // @see Drupal\Core\File\FileSystemInterface::prepareDirectory().
    try {
      // Let mkdir() recursively create directories and use the default
      // directory permissions.
      $success = $this->mkdir($directory, NULL, TRUE);
      if ($success) {
        return TRUE;
      }
    }
    catch (\Exception $e) {
      // @todo write exception handler to handle Flysystem Exception, and
      // return a proper return code, which is FALSE.
    }
    // If the operation failed, check again if the directory was created
    // by another process/server, only report a failure if not. In this case
    // we still need to ensure the directory is writable.
    if (!$wrapper->directoryExists($directory)) {
      return FALSE;
    }
    return $this->chmod($directory);
  }

  /**
   * Converts Drupal's FileSystem::createFilename() method to leverage
   * League\Flysystem\FilesystemOperator methods.
   *
   * @param \League\Flysystem\FilesystemOperator $wrapper
   *   Drupal StreamWrapper service.
   * @param string $separator
   *   The pathname separator.
   * @param string $destination
   *   The destination filename.
   * @param string $basename
   *   The filename.
   * @param string $directory
   *   The directory or parent URI.
   *
   * @return string
   *   File path consisting of $directory and a unique filename based off
   *   of $basename.
   *
   * @todo finish writing, see inline todo comments.
   * @todo reevaluate against \Drupal\Core\File\FileSystem::createFilename().
   */
  protected function createFilenameFs($wrapper, $separator, $destination, $basename, $directory): string {
    try {
      $exists = $wrapper->fileExists($destination);
      if ($exists) {
        // Destination file already exists, generate an alternative.
        $pos = strrpos($basename, '.');
        if ($pos !== FALSE) {
          $name = substr($basename, 0, $pos);
          $ext = substr($basename, $pos);
        }
        else {
          $name = $basename;
          $ext = '';
        }

        $counter = 0;
        do {
          $destination = $directory . $separator . $name . '_' . $counter++ . $ext;
        } while ($wrapper->fileExists($destination));

      }
    }
    catch (\Exception $e) {
      // @todo write exception handler to handle Flysystem Exception, and
      // return a proper return code, which is FALSE.
    }
    return $destination;
  }

  /**
   * Converts Drupal's FileSystem::createFilename() method to leverage
   * League\Flysystem\FilesystemOperator methods.
   *
   * @param \League\Flysystem\FilesystemOperator $wrapper
   *   Drupal StreamWrapper service.
   * @param string $basename
   *   The base file name, calculated.
   * @param string $destination
   *   The desired final URI or filepath.
   * @param \Drupal\Core\File\FileExists|int $fileExists
   *   Replace behavior when the destination file already exists.
   *
   * @return string|bool
   *   The destination filepath, or FALSE if the file already exists
   *   and FileExists::Error is specified.
   *
   * @todo finish writing, see inline todo comments.
   */
  protected function getDestinationFilenameFs($wrapper, $basename, $destination, $fileExists): string|bool {
    try {
      if ($wrapper->fileExists($destination)) {
        switch ($fileExists) {
          case FileExists::Replace:
            // Do nothing here, we want to overwrite the existing file.
            break;

          case FileExists::Rename:
            // Hmm, we are using the original Drupal FileSystem::dirname()
            // method here, will it error out?  Do we actually need to call this?
            $directory = $this->dirname($destination);
            $destination = $this->createFilename($basename, $directory);
            break;

          case FileExists::Error:
            // Error reporting handled by calling function.
            return FALSE;
        }
      }
    }
    catch (\Exception $e) {
      // @todo write exception handler to handle Flysystem Exception, and
      // return a proper return code, which is FALSE.
    }
    return $destination;
  }
}