<?php

namespace Drupal\flysystem\FlyStream;

use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * FlyStream Stram Wrapper implementation.
 */
class FlyStreamWrapper extends FlyStreamWrapperBase implements FlyStreamWrapperInterface, StreamWrapperInterface {

  use StringTranslationTrait;

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

  /**
   * Instance URI (stream).
   *
   * A stream is referenced as "scheme://target".
   *
   * @var string
   */
  protected $uri;

  /**
   * Scheme identifier.
   *
   * @var string
   */
  protected $scheme = '';

  /**
   * StreamWrapper type.
   *
   * @var int
   */
  protected static $type = StreamWrapperInterface::NORMAL;

  /**
   * {@inheritdoc}
   */
  public static function getType() {
    return self::$type;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->t("Flysystem Stream Wrapper: @scheme", ['@scheme' => $this->scheme]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t("Flysystem Stream Wrapper: @scheme", ['@scheme' => $this->scheme]);
  }

  /**
   * {@inheritdoc}
   */
  public function getExternalUrl() {
    return $this->uri;
  }

  /**
   * {@inheritdoc}
   */
  public function setUri($uri) {
    $this->uri = $uri;
  }

  /**
   * {@inheritdoc}
   */
  public function getUri() {
    return $this->uri;
  }

  /**
   * {@inheritdoc}
   */
  public function realpath() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function dirname($uri = NULL): string {
    if (!isset($uri)) {
      $uri = $this->uri;
    }

    [$scheme, $target] = explode('://', $uri, 2);
    $dirname = dirname($target);

    if ($dirname == '.') {
      $dirname = '';
    }

    return $scheme . '://' . $dirname;
  }

}
