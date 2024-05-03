<?php

declare(strict_types=1);

namespace Drupal\flysystem_adapter\Plugin;

/**
 * Interface for flysystem_adapter_config plugins.
 */
interface FlysystemAdapterConfigInterface {

  /**
   * Gets the Translated adapter plugin description.
   *
   * @return string
   *   Translated adapter plugin description.
   */
  public function description();

  /**
   * Returns the translated adapter plugin label.
   *
   * @return string
   *   Translated adapter config plugin label.
   */
  public function label(): string;

  /**
   * Gets the adapter plugin id.
   *
   * @return string
   *   Adapter id.
   */
  public function id(): string;

}
