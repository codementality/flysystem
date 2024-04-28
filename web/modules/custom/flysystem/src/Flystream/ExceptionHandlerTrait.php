<?php

namespace Drupal\flysystem\FlyStream;

use League\Flysystem\FilesystemException;

/**
 * ExceptionHandler trait for Stream Wrappers.
 */
trait ExceptionHandlerTrait {

  /**
   * Triggers error handler.
   *
   * @param \League\Flysystem\FilesystemException $e
   *   FilesystemException thrown by FilesystemOperator.
   *
   * @return bool
   *   Returns a FALSE value after triggering error.
   */
  protected function triggerError(FilesystemException $e): bool {
    trigger_error($this->collectErrorMessage($e), E_USER_WARNING);

    return FALSE;
  }

  /**
   * Collects the information abou the error message thrown in the exception.
   *
   * @param \Throwable $e
   *   Throwable exception.
   *
   * @return string
   *   Error message to return to handler.
   */
  protected function collectErrorMessage(\Throwable $e): string {
    $message = $e->getMessage();
    $previous = $e->getPrevious();
    if (!$previous instanceof \Throwable) {
      return $message;
    }

    return $message . ' : ' . $this->collectErrorMessage($previous);
  }

}
