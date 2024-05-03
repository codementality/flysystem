<?php

declare(strict_types=1);

namespace Drupal\flysystem_adapter\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\flysystem_adapter\FlysystemAdapterInterface;

/**
 * Defines the flysystem adapter entity type.
 *
 * @ConfigEntityType(
 *   id = "flysystem_adapter",
 *   label = @Translation("Flysystem adapter"),
 *   label_collection = @Translation("Flysystem adapters"),
 *   label_singular = @Translation("flysystem adapter"),
 *   label_plural = @Translation("flysystem adapters"),
 *   label_count = @PluralTranslation(
 *     singular = "@count flysystem adapter",
 *     plural = "@count flysystem adapters",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\flysystem_adapter\FlysystemAdapterListBuilder",
 *     "form" = {
 *       "add" = "Drupal\flysystem_adapter\Form\FlysystemAdapterForm",
 *       "edit" = "Drupal\flysystem_adapter\Form\FlysystemAdapterForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *   },
 *   config_prefix = "flysystem_adapter",
 *   admin_permission = "administer flysystem_adapter",
 *   links = {
 *     "canonical" = "/admin/config/media/flysystem-adapter/{flysystem_adapter}",
 *     "add-form" = "/admin/config/media/flysystem-adapter/add",
 *     "edit-form" = "/admin/config/media/flysystem-adapter/{flysystem_adapter}/edit",
 *     "delete-form" = "/admin/config/media/flysystem-adapter/{flysystem_adapter}/delete",
 *     "collection" = "/admin/config/media/flysystem-adapter",
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "description",
 *     "adapter_type",
 *   },
 * )
 */
class FlysystemAdapter extends ConfigEntityBase implements FlysystemAdapterInterface {

  /**
   * The adapter ID.
   */
  protected string $id;

  /**
   * The adapter label.
   */
  protected string $label;

  /**
   * The adapter description.
   */
  protected string $description;

  /**
   * The enabled/disabled status of the configuration entity.
   *
   * @var bool
   */
  protected $status = TRUE;

  /**
   * The ID of the adapter plugin.
   *
   * @var string|NULL
   */
  protected $adapter_type = NULL;

  /**
   * The adapter plugin configuration.
   *
   * @var array
   */
  protected $adapterPluginConfig = [];

  /**
   * {@inheritdoc}
   */
  public function description(): string|NULL {
    return $this->description ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function adapterPluginConfig() {
    return $this->adapterPluginConfig;
  }

  /**
   * Get Adapter Plugin id.
   *
   * @return string|NULL
   */
  public function adapterPluginId(): string|NULL {
    return $this->adapter_type ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setAdapterPluginConfig(array $adapter_plugin_config) {
    $this->adapterPluginConfig = $adapter_plugin_config;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hasValidAdapter() {
    $adapter_config_plugin_definition = \Drupal::service('plugin.manager.flysystem_adapter_config')->getDefinition($this->adapterPluginId(), FALSE);
    return !empty($adapter_config_plugin_definition);
  }

}
