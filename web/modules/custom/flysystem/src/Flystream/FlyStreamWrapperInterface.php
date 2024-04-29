<?php

namespace Drupal\flysystem\FlyStream;

use League\Flysystem\Visibility;

/**
 * Interface for FlyStream stream wrappers.
 */
interface FlyStreamWrapperInterface {

  public const LOCK_STORE = 'lock_store';
  public const LOCK_TTL = 'lock_ttl';

  public const IGNORE_VISIBILITY_ERRORS = 'ignore_visibility_errors';

  public const EMULATE_DIRECTORY_LAST_MODIFIED = 'emulate_directory_last_modified';

  public const UID = 'uid';
  public const GID = 'gid';

  public const VISIBILITY_FILE_PUBLIC = 'visibility_file_public';
  public const VISIBILITY_FILE_PRIVATE = 'visibility_file_private';
  public const VISIBILITY_DIRECTORY_PUBLIC = 'visibility_directory_public';
  public const VISIBILITY_DIRECTORY_PRIVATE = 'visibility_directory_private';
  public const VISIBILITY_DEFAULT_FOR_DIRECTORIES = 'visibility_default_for_directories';

  public const DEFAULT_CONFIGURATION = [
    // Const LOCK_STORE.
    self::LOCK_STORE => 'flock:///tmp',
    // Const LOCK_TTL.
    self::LOCK_TTL => 300,
    // Const IGNORE_VISIBILITY_ERRORS.
    self::IGNORE_VISIBILITY_ERRORS => FALSE,
    self::EMULATE_DIRECTORY_LAST_MODIFIED => FALSE,

    self::UID => NULL,
    self::GID => NULL,

    self::VISIBILITY_FILE_PUBLIC => 0644,
    self::VISIBILITY_FILE_PRIVATE => 0600,
    self::VISIBILITY_DIRECTORY_PUBLIC => 0755,
    self::VISIBILITY_DIRECTORY_PRIVATE => 0700,
    self::VISIBILITY_DEFAULT_FOR_DIRECTORIES => Visibility::PRIVATE,
  ];

  public const STATS_ZERO = [0, 'dev', 1, 'ino', 3, 'nlink', 6, 'rdev'];
  public const STATS_MODE = [2, 'mode'];
  public const STATS_SIZE = [7, 'size'];
  public const STATS_TIME = [8, 'atime', 9, 'mtime', 10, 'ctime'];
  public const STATS_MINUS_ONE = [11, 'blksize', 12, 'blocks'];

}
