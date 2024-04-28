<?php

declare(strict_types=1);

namespace Drupal\flysystem\Adapters;

use League\Flysystem\FilesystemException;

/**
 * Exception thrown in MissingAdapter object.
 */
class MissingAdapterException extends \RuntimeException implements FilesystemException {

  public function __construct(string $message = "", int $code = 0, ?\Throwable $previous = NULL) {
    throw new \Exception($message, $code, $previous);
  }

}
