<?php

namespace Drupal\flysystem\Plugin;

/**
 * Interface definition for Flysystem Adapter plugins.
 */
interface FlysystemAdapterPluginInterface {

  /**
   * Returns the Flysystem adapter.
   *
   * Plugins should not keep references to the adapter.
   *
   * @return \League\Flysystem\FilesystemAdapter
   *   The Flysystem adapter.
   */
  public function getAdapter();

  /**
   * Returns a web accessible URL for the resource.
   *
   * This function should return a URL that can be embedded in a web page
   * and accessed from a browser. For example, the external URL of
   * "youtube://xIpLd0WQKCY" might be
   * "http://www.youtube.com/watch?v=xIpLd0WQKCY".
   *
   * @param string $uri
   *   The URI to provide a URL for.
   * @param bool $isSecureRequest
   *   TRUE for secure requests, FALSE if insecure requests allowed.
   *
   * @return string
   *   Returns a string containing a web accessible URL for the resource.
   */
  public function getExternalUrl($uri, $isSecureRequest = TRUE);

  /**
   * Checks the sanity of the filesystem.
   *
   * If this is a local filesystem, .htaccess file should be in place.
   *
   * @return array
   *   A list of error messages.
   */
  public function ensure($force = FALSE);

  /**
   * Get Adapter configuration.
   *
   * @return array
   *   Configuration array.
   */
  public function getConfiguration();

  /**
   * Set Adapter configuration.
   *
   * @param array $adapterConfig
   *   Adapter configuration array.
   */
  public function setConfiguration($adapterConfig);

}
