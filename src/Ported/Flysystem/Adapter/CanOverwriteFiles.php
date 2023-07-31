<?php

namespace Drupal\flysystem\Ported\Flysystem\Adapter;

/**
 * Ported from league/flysystem v1.
 * @see https://raw.githubusercontent.com/thephpleague/flysystem/1.x/src/Adapter/CanOverwriteFiles.php.
 *
 * Adapters that implement this interface let the Filesystem know that files can be overwritten using the write
 * functions and don't need the update function to be called. This can help improve performance when asserts are disabled.
 */
interface CanOverwriteFiles
{
}
