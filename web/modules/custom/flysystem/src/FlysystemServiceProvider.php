<?php

namespace Drupal\flysystem;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\Core\Site\Settings;

/**
 * Flysystem dependency injection container.
 *
 * @todo We will need to keep this, but extend ServiceProviderBase so we can alter core service providers
 * @see https://www.bounteous.com/insights/2017/04/19/drupal-how-override-core-drupal-8-service
 * @see https://www.drupal.org/docs/drupal-apis/services-and-dependency-injection/altering-existing-services-providing-dynamic-services
 */
class FlysystemServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {

    foreach (Settings::get('flysystem', []) as $scheme => $settings) {

      // Just some sanity checking, so things don't explode.
      if (empty($settings['driver'])) {
        continue;
      }
      // Registers the FlysystemStreamWrapper.
      // @todo Revisit if this is needed,
      $container
        ->register('flysystem_stream_wrapper.' . $scheme, 'Drupal\flysystem\FlyStream\StreamWrapper')
        ->addTag('stream_wrapper', ['scheme' => $scheme]);

    }
  }

}
