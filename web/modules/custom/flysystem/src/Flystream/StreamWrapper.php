<?php

namespace Drupal\flysystem\Flystream;

use Drupal\Core\StreamWrapper\StreamWrapperInterface;

/**
 * Flystream Stream Wrapper implementation of abastract classes.
 */
class StreamWrapper extends StreamWrapperBase {

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
    return $this->t('Flysystem StreamWrapper @adapter: @scheme',
    [
      '@adapter' => $this->adapter,
      '@scheme' => $this->scheme,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Flysystem StreamWrapper @scheme, implements Adapter @adapter',
    [
      '@scheme' => $this->scheme,
      '@adapter' => $this->adapter,
    ]);
  }

}
