<?php

namespace Drupal\flysystem\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\flysystem\Plugin\FlysystemAdapterPluginInterface;

/**
 * Provides an interface for defining Flysystem StreamWrapper Config entities.
 *
 * @method string id()
 *
 * @see Drupal\s3fs submodule Drupal\s3fs+streamwrapper\Entity\S3StreamWrapperEntityInterface
 */
interface FlysystemAdapterEntityInterface extends ConfigEntityInterface {

  /**
   * Retrieves the StreamWrapper's description.
   *
   * @return string
   *   The description of the StreamWrapper.
   */
  public function getDescription(): string;

  /**
   * Returns the adapter plugin id.
   *
   * @return string|null
   *   Adapter plugin type id, or null if not yet set.
   */
  public function getPluginId(): ?string;

  /**
   * Obtain a configured adapter Plugin.
   *
   * @return \Drupal\flysystem\Plugin\FlysystemAdapterPluginInterface
   *   A configured Flysystem Adapter Plugin.
   */
  public function getAdapterPlugin(): FlysystemAdapterPluginInterface;

  /**
   * Has valid Adapter plugin.
   *
   * @return bool
   *   True if configured plugin exists.
   */
  public function hasAdapterPlugin(): bool;

  /**
   * Obtain the adapter plugin config entity.
   *
   * @return array
   *   Config entity for a configured adapter plugin.
   */
  public function getAdapterPluginConfig(): array;

  /**
   * Set the adapter plugin config.
   *
   * @param array $adapterConfig
   *   Configuration to overwrite settings with.
   *
   * @return $this
   */
  public function setAdapterConfig(array $adapterConfig): static;

  /*
   * The following functions are from FlysystemAdapterPluginInterface
   */

  /**
   * Drupal StreamWrapper functions.
   */

  /**
   * Returns a web accessible URL for the resource.
   *
   * The format of the returned URL will be different depending on how the
   * Flysystem integration has been configured.
   *
   * @param string $uri
   *   The URI of the file to generate URL for.
   * @param bool $isSecureRequest
   *   Is the request loaded via a secure(https) page.
   *
   * @return string
   *   A web accessible URL for the resource.
   */
  public function getExternalUrl(string $uri, bool $isSecureRequest = FALSE): string;

}
