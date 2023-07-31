<?php

namespace Drupal\flysystem\Plugin;

use Drupal\flysystem\Ported\Flysystem\Util;

/**
 * Ported from twistor/flysystem-stream-wrapper. 
 * @see https://raw.githubusercontent.com/twistor/flysystem-stream-wrapper/master/src/Flysystem/Plugin/Touch.php.
 */
class Touch extends AbstractPlugin
{
    /**
     * @inheritdoc
     */
    public function getMethod()
    {
        return 'touch';
    }

    /**
     * Emulates touch().
     *
     * @param string $path
     *
     * @return bool True on success, false on failure.
     */
    public function handle($path)
    {
        $path = Util::normalizePath($path);

        $adapter = $this->filesystem->getAdapter();

        if ($adapter->has($path)) {
            return true;
        }

        return (bool) $adapter->write($path, '', $this->defaultConfig());
    }
}