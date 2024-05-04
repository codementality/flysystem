<?php

namespace Drupal\flysystem\Plugin\Adapter;

use Drupal\Core\Logger\RfcLogLevel;
use Drupal\flysystem\Adapters\FlysystemMissingAdapter;

/**
 * Drupal plugin for the "NullAdapter" Flysystem adapter.
 *
 * @FlysystemAdapter(
 *   id = "missing"
 *   label = "Local Adapter",
 *   description = "Flysystem Local Adapter"
 * )
 */
class MissingAdapter extends AdapterPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getAdapter() {
    return new FlysystemMissingAdapter();
  }

  /**
   * {@inheritdoc}
   */
  public function getExternalUrl($uri, $isSecureRequest = FALSE) {
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

  /**
   * {@inheritdoc}
   *
   * @todo Complete this method.
   */
  public function getConfiguration() {
    return [];
  }

  /**
   * {@inheritdoc}
   *
   * @todo Complete this method.
   */
  public function setConfiguration($adapterConfig) {
  }

}
