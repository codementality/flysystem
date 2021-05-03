<?php

namespace Drupal\flysystem\Asset;

use Drupal\Core\Asset\CssCollectionOptimizer as DrupalCssCollectionOptimizer;

/**
 * Optimizes CSS assets.
 */
class CssCollectionOptimizer extends DrupalCssCollectionOptimizer {

  use SchemeExtensionTrait;

  /**
   * {@inheritdoc}
   */
  public function deleteAll() {
    $this->state->delete('drupal_css_cache_files');
    /** @var \Drupal\Core\File\FileSystem $file_system */
    $file_system = \Drupal::service('file_system');
    $delete_stale = static function ($uri) use ($file_system) {
      // Default stale file threshold is 30 days (2592000 seconds).
      $stale_file_threshold = \Drupal::config('system.performance')->get('stale_file_threshold') ?? 2592000;
      if (\Drupal::time()->getRequestTime() - filemtime($uri) > $stale_file_threshold) {
        $file_system->delete($uri);
      }
    };
    $css_dir = $this->getSchemeForExtension('css') . '://css';
    if (is_dir($css_dir)) {
      $file_system->scanDirectory($css_dir, '/.*/', ['callback' => $delete_stale]);
    }
  }

}
