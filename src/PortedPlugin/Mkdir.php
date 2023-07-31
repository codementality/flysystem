<?php

namespace Drupal\flysystem\PortedPlugin;

use Drupal\flysystem\Ported\Flysystem\FileNotFoundException;
use Drupal\flysystem\Ported\Flysystem\Util;

/**
 * Ported from twistor/flysystem-stream-wrapper.
 * @see https://raw.githubusercontent.com/twistor/flysystem-stream-wrapper/master/src/Flysystem/Plugin/Mkdir.php.
 */
class Mkdir extends AbstractPlugin
{
    /**
     * @inheritdoc
     */
    public function getMethod()
    {
        return 'mkdir';
    }

    /**
     * Creates a directory.
     *
     * @param string $dirname
     * @param int $mode
     * @param int $options
     *
     * @return bool True on success, false on failure.
     *
     * @throws \Drupal\flysystem\Ported\Flysystem\FileNotFoundException
     */
    public function handle($dirname, $mode, $options)
    {
        $dirname = Util::normalizePath($dirname);

        $adapter = $this->filesystem->getAdapter();

        // If recursive, or a single level directory, just create it.
        if (($options & STREAM_MKDIR_RECURSIVE) || strpos($dirname, '/') === false) {
            return (bool) $adapter->createDir($dirname, $this->defaultConfig());
        }

        if ( ! $adapter->has(dirname($dirname))) {
            throw new FileNotFoundException($dirname);
        }

        return (bool) $adapter->createDir($dirname, $this->defaultConfig());
    }
}