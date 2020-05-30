<?php

namespace Drupal\flysystem\Asset;

use Drupal\Core\Asset\JsCollectionOptimizer as DrupalJsCollectionOptimizer;

/**
 * Optimizes JavaScript assets.
 */
class JsCollectionOptimizer extends DrupalJsCollectionOptimizer {

  use SchemeExtensionTrait;

  /**
   * {@inheritdoc}
   */
  public function deleteAll() {
    $this->state->delete('system.js_cache_files');
    /** @var \Drupal\Core\File\FileSystem $file_system */
    $file_system = \Drupal::service('file_system');
    $delete_stale = function ($uri) use ($file_system) {
      // Default stale file threshold is 30 days.
      if (\Drupal::time()->getRequestTime() - filemtime($uri) > \Drupal::config('system.performance')->get('stale_file_threshold')) {
        $file_system->delete($uri);
      }
    };
    $file_system->scanDirectory($this->getSchemeForExtension('js') . '://js', '/.*/', ['callback' => $delete_stale]);
  }

}
