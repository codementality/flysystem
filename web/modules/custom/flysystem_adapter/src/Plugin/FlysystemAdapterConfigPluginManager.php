<?php

declare(strict_types=1);

namespace Drupal\flysystem_adapter\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\flysystem_adapter\Annotation\FlysystemAdapterConfig;

/**
 * FlysystemAdapterConfig plugin manager.
 */
class FlysystemAdapterConfigPluginManager extends DefaultPluginManager {

  /**
   * Constructs the object.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/FlysystemAdapterConfig', $namespaces, $module_handler, FlysystemAdapterConfigInterface::class, FlysystemAdapterConfig::class);
    $this->alterInfo('flysystem_adapter_config_info');
    $this->setCacheBackend($cache_backend, 'flysystem_adapter_config_plugins');
  }

}
