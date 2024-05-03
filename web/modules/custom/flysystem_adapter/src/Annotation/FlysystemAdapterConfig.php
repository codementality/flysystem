<?php

declare(strict_types=1);

namespace Drupal\flysystem_adapter\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines flysystem_adapter_config annotation object.
 *
 * @Annotation
 */
class FlysystemAdapterConfig extends Plugin {

  /**
   * The plugin ID.
   */
  public string $id;

  /**
   * The human-readable name of the plugin.
   *
   * @ingroup plugin_translatable
   */
  public string $title;

  /**
   * The description of the plugin.
   *
   * @ingroup plugin_translatable
   */
  public string $description;

}
