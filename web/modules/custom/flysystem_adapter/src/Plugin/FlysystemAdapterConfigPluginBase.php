<?php

declare(strict_types=1);

namespace Drupal\flysystem_adapter\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\flysystem_adapter\Plugin\FlysystemAdapterConfigInterface;

/**
 * Base class for flysystem_adapter_config plugins.
 */
abstract class FlysystemAdapterConfigPluginBase extends PluginBase implements FlysystemAdapterConfigInterface {

  use StringTranslationTrait;

  /**
   * The adapterPlugin this adapter is configured for.
   *
   * @var \Drupal\flysystem_adapter\FlysystemAdapterInterface
   */
  protected $adapterPlugin;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    // Cast the label to a string since it is a TranslatableMarkup object.
    return $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = $configuration + $this->defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function getAdapterPluginConfig() {

  }

  /**
   * {@inheritdoc}
   */
  public function setAdapterPluginConfig() {

  }

}
