<?php

namespace Drupal\flysystem\Compiler;

use Drupal\flysystem\FlyStream\FlyStreamWrapper;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * Create container entries for the schemes that are enabled.
 *
 * The core RegisterStreamWrapperPass() must be called after this pass to
 * actually register the container entries created with this pass.
 *
 * @see (v4.0) Drupal\s3fs submodule: Drupal\s3fs_streamwrapper\S3fsStreamwrapperServiceProvider
 */
class FlyRegisterStreamWrappers implements CompilerPassInterface {

  /**
   * {@inheritdoc}
   */
  public function process(ContainerBuilder $container): void {
    /** @var \Drupal\Core\Config\CachedStorage $config_storage */
    $config_storage = $container->get('config.storage');
    $entity_config_names = $config_storage->listAll('adapter.flysystem_adapter');
    $configured_entities = $config_storage->readMultiple($entity_config_names);

    foreach ($configured_entities as $key) {
      if (empty($key)) {
        // This should never happen, however if it does skip this entry.
        continue;
      }

      if ($key['status'] !== TRUE) {
        // Scheme is not enabled.
        continue;
      }

      $service_definition = new Definition(FlyStreamWrapper::class);
      $service_definition->setTags(
        [
          'flysysytem_adapter' => [
            [
              'scheme' => $key['id'],
            ],
          ],
        ],
      );
      $service_definition->setPublic(TRUE);
      $container->setDefinition('flysystem_adapter.' . $key['id'], $service_definition);

    }
  }

}
