<?php

namespace Drupal\flysystem\FlyStream;

/**
 * FlyStream Stram Wrapper implementation.
 */
class FlyStreamWrapper extends FlyStreamWrapperBase implements FlyStreamWrapperInterface {

  /**
   * FlysystemOperator instances.
   *
   * @var array<\League\Flysystem\FilesystemOperator>
   */
  public static $filesystems;

  /**
   * Configuration settings.
   *
   * @var array
   */
  public static $config;

}
