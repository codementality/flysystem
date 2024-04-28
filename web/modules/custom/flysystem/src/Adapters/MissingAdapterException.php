<?php

declare(strict_types=1);

namespace Drupal\flysystem\Adapters;

use RuntimeException;
use Throwable;
use League\Flysystem\FilesystemException;

class MissingAdapterException extends RuntimeException implements FilesystemException {
  final public function __construct(string $message = "", int $code = 0, ?Throwable $previous = null) {
    parent::__construct($message, $code, $previous);
  }
}