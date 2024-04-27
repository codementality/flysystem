<?php

namespace Drupal\flysystem\Flystream;

use Elazar\Flystream\StreamWrapper as FlystreamStreamWrapper;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManager;
use Drupal\Core\StringTranslation\StringTranslationTrait;

abstract class StreamWrapperBase extends FlystreamStreamWrapper implements StreamWrapperInterface {  

  use StringTranslationTrait;

  /**
   * Instance uri referenced as "<scheme>://key".
   *
   * @var string|null
   */
  protected $uri = NULL;

  /**
   * The destination scheme for the stream wrapper
   *
   * @var string|null
   */
  protected $scheme = NULL;

  /**
   * {@inheritdoc}
   */
  public static function getType(): int {
    return StreamWrapperInterface::NORMAL;
  }

  /**
   * {@inheritdoc}
   */
  public function getName(): string {
    return $this->t('Abstract Class StreamWrapperBaser @scheme', ['@scheme' => $this->scheme]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Abstract Class StreamWrapperBase: @scheme. This class is extended by StreamWrapper implementations.', ['@scheme' => $this->scheme]);
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
  public function realpath() {
    return FALSE;
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
    if ($this->uri == NULL) {
      throw new \Exception('Call getUri() before callings setUri() is not supported');
    }
    return $this->uri;
  }
  /**
   * {@inheritdoc}
   */
  public function dirname($uri = NULL) {
    if (!isset($uri)) {
      $uri = $this->uri;
    }

    list($scheme, $target) = explode('://', $uri, 2);
    $dirname = dirname($target);

    if ($dirname == '.') {
      $dirname = '';
    }
    $this->scheme = $scheme;
  
    return $scheme . '://' . $dirname;
  }

}