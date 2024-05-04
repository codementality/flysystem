<?php

namespace Drupal\flysystem\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Flysystem adapter plugin.
 *
 * For a working example:
 *
 * @see \Drupal\flysystem_local\Plugin\flysystem\Adapter\LocalAdapter
 * @see plugin_api
 *
 * @Annotation
 */
class FlysystemAdapter extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * Human readable name of Adapter plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * The Adapter description.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

}
