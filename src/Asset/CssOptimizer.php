<?php

namespace Drupal\flysystem\Asset;

use Drupal\Core\Asset\CssOptimizer as DrupalCssOptimizer;

/**
 * Changes Drupal\Core\Asset\CssOptimizer to not remove absolute URLs.
 *
 * @codeCoverageIgnore
 */
class CssOptimizer extends DrupalCssOptimizer {
  /**
   * The FileUrl Generator Service.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected $fileUrlGenerator;

  /**
   * Constructs a new CssOptimizer object.
   *
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $fileUrlGenerator
   *   The FileUrl Generator service.
   */
  public function __construct(FileUrlGeneratorInterface $fileUrlGenerator) {
    parent::__construct($fileUrlGenerator);
    $this->fileUrlGenerator = $fileUrlGenerator;
  }

  /**
   * {@inheritdoc}
   */
  public function rewriteFileURI($matches): string {
    // Prefix with base and remove '../' segments where possible.
    $path = $this->rewriteFileURIBasePath . $matches[1];
    $last = '';
    while ($path != $last) {
      $last = $path;
      $path = preg_replace('`(^|/)(?!\.\./)([^/]+)/\.\./`', '$1', $path);
    }
    // file_url_transform_relative() was removed here.
    return 'url(' . $this->fileUrlGenerator->generate($path)?->toString() . ')';
  }

}
