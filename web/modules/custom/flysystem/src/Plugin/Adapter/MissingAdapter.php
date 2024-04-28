<?php

namespace Drupal\flysystem\Plugin\Adapter;

use Drupal\Core\Logger\RfcLogLevel;
use Drupal\flysystem\Adapters\FlysystemMissingAdapter;
use Drupal\flysystem\Plugin\FlysystemPluginInterface;

/**
 * Drupal plugin for the "NullAdapter" Flysystem adapter.
 *
 * @FlysystemAdapter(id = "missing")
 */
class MissingAdapter implements FlysystemPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function getAdapter() {
    return new FlysystemMissingAdapter();
  }

  /**
   * {@inheritdoc}
   */
  public function getExternalUrl($uri) {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function ensure($force = FALSE) {
    return [
      [
        'severity' => RfcLogLevel::ERROR,
        'message' => 'The Flysystem driver is missing.',
        'context' => [],
      ],
    ];
  }

}
