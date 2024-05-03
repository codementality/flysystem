<?php

declare(strict_types=1);

namespace Drupal\flysystem_adapter;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a flysystem adapter entity type.
 */
interface FlysystemAdapterInterface extends ConfigEntityInterface {

  /**
   * Gets the Adapter description.
   *
   * @return string|null
   *   Adapter description.
   */
  public function description(): string|NULL;

  /**
   * Retrieves the configuration of this adapter config plugin.
   *
   * @return array
   *   An associative array with the adapter config plugin configuration.
   */
  public function adapterPluginConfig();

  /**
   * Get Adapter Plugin id.
   *
   * @return string|NULL
   */
  public function adapterPluginId(): string|NULL;

  /**
   * Sets the configuration of this adapter config plugin.
   *
   * @param array $adapter_plugin_config
   *   The new configuration for the adapter plugin.
   *
   * @return $this
   */
  public function setAdapterPluginConfig(array $adapter_plugin_config);

 /**
   * Determines whether the adapter config plugin is valid.
   *
   * @return bool
   *   TRUE if the plugin is valid, FALSE otherwise.
   */
  public function hasValidAdapter();
}
