<?php

namespace Drupal\flysystem\Ported\Flysystem;

/**
 * Ported from league/flysystem v1.
 * @see https://raw.githubusercontent.com/thephpleague/flysystem/1.x/src/PluginInterface.php.
 */
interface PluginInterface
{
    /**
     * Get the method name.
     *
     * @return string
     */
    public function getMethod();

    /**
     * Set the Filesystem object.
     *
     * @param FilesystemInterface $filesystem
     */
    public function setFilesystem(FilesystemInterface $filesystem);
}