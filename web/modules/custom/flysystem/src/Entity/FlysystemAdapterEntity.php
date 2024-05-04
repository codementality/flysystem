<?php

namespace Drupal\flysystem\Entity;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\flysystem\Plugin\FlysystemAdapterPluginInterface;

/**
 * Defines the Flysystem Adapter config entity.
 *
 * @ConfigEntityType(
 *   id = "flysystem_adapter",
 *   label = @Translation("Flysystem Adapter Configuration"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\flysystem\Page\FlysystemAdapterListBuilder",
 *     "form" = {
 *       "add" = "Drupal\flysystem\Form\FlysystemAdapterEntityForm",
 *       "edit" = "Drupal\flysystem\Form\FlysystemAdapterEntityForm",
 *       "delete" = "Drupal\flysystem\Form\FlysystemAdapterEntityDeleteForm",
 *       "disable" = "Drupal\flysystem\Form\FlysystemAdapterEntityDisableConfirmForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\flysystem\Routing\FlysystemAdapterEntityHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "flysystem_adapter",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "description" = "description",
 *     "adapter_type" = "adapter_type"
 *   },
 *   links = {
 *     "canonical" = "/admin/config/media/flysystem-adapters/{flysystem_adapter}",
 *     "add-form" = "/admin/config/media/flysystem-adapters/add",
 *     "edit-form" = "/admin/config/media/flysystem-adapters/{flysystem_adapter}/edit",
 *     "delete-form" = "/admin/config/media/flysystem-adapters/{flysystem_adapter}/delete",
 *     "collection" = "/admin/config/media/flysystem-adapters"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "uuid",
 *     "description",
 *     "adapter_type"
 *   }
 * )
 *
 * @method string id()
 */
class FlysystemAdapterEntity extends ConfigEntityBase implements FlysystemAdapterEntityInterface {

  /**
   * The Flysystem Adapter Config ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Flysystem Adapter Config label.
   *
   * @var string
   */
  protected $label;

  /**
   * The Flysystem Adapter description.
   *
   * @var string
   */
  protected $description = '';

  /**
   * Plugin ID.
   *
   * @var string
   */
  protected $pluginId;

  /**
   * The name of the Flysystem Adapter Plugin Type for this entity.
   *
   * @var string
   */
  protected $adapterPluginType;

  /**
   * Settings for this adapter during plugin configuration.
   *
   * @var array
   */
  protected $adapterConfig = [];

  /**
   * The adapter plugin instance.
   *
   * @var \Drupal\flysystem\Plugin\FlysystemAdapterPluginInterface|null
   */
  protected $adapterPlugin = NULL;

  /**
   * {@inheritdoc}
   */
  public function getDescription(): string {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginId(): ?string {
    return $this->pluginId;
  }

  /**
   * {@inheritdoc}
   */
  public function getAdapterPlugin(): FlysystemAdapterPluginInterface {
    if (!$this->adapterPlugin) {
      /** @var \Drupal\flysystem\Plugin\FlysystemAdapterPluginManager $adapterPluginManager */
      $adapterPluginManager = \Drupal::service('plugin.manager.flysystem_adapter');
      $config = $this->adapterConfig;
      $config['#flysystem-adapter'] = $this;
      if ($this->getPluginId() == NULL) {
        throw new \Exception("No plugin ID specified.");

      }
      try {
        $this->adapterPlugin = $adapterPluginManager->createInstance($this->getPluginId(), $config);
      }
      catch (PluginException $e) {
        $pluginId = $this->getPluginId();
        $label = $this->label();
        throw new \Exception("The plugin with ID '$pluginId' could not be retrieved for Flysystem Adapter '$label'.");
      }
    }
    return $this->adapterPlugin;
  }

  /**
   * {@inheritdoc}
   */
  public function hasAdapterPlugin(): bool {

    if ($this->getPluginId() == NULL) {
      return FALSE;
    }
    $backend_plugin_definition = \Drupal::service('plugin.manager.flysystem_adapter')->getDefinition($this->getPluginId(), FALSE);
    return !empty($backend_plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function getAdapterPluginConfig(): array {
    return $this->adapterConfig;
  }

  /**
   * {@inheritdoc}
   */
  public function getAdapterPluginType(): string {
    return $this->adapterPluginType;
  }

  /**
   * {@inheritdoc}
   */
  public function setAdapterConfig(array $adapterConfig): static {
    $this->adapterConfig = $adapterConfig;
    // In case the plugin is already loaded, make sure the configuration
    // stays in sync.
    if ($this->adapterPlugin
      && $this->getAdapterPlugin()->getConfiguration() !== $adapterConfig) {
      $this->getAdapterPlugin()->setConfiguration($adapterConfig);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getExternalUrl(string $uri, bool $isSecureRequest = FALSE): string {
    $uri = $this->resolvePath($uri);
    return $this->getAdapterPlugin()->getExternalUrl($uri, $isSecureRequest);
  }

  /**
   * @param string $uri
   *   URI to resolve.
   *
   * @return string
   *   Resolved URI.
   *
   * @todo complete method.
   * @todo Determine if this is defined on the interface or declared protedted.
   */
  protected function resolvePath($uri) {
    return '';
  }

}
