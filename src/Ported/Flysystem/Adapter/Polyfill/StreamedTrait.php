<?php

namespace Drupal\flysystem\Ported\Flysystem\Adapter\Polyfill;

/**
 * Ported from league/flysystem v1.
 * @see https://raw.githubusercontent.com/thephpleague/flysystem/1.x/src/Adapter/Polyfill/StreamedTrait.php.
 */
trait StreamedTrait
{
    use StreamedReadingTrait;
    use StreamedWritingTrait;
}