<?php

namespace Drupal\flysystem\Ported\Flysystem\Plugin;

use Drupal\flysystem\Ported\Flysystem\FilesystemInterface;
use Drupal\flysystem\Ported\Flysystem\PluginInterface;

/**
 * Ported from league/flysystem v1.
 * @see https://raw.githubusercontent.com/thephpleague/flysystem/1.x/src/Plugin/AbstractPlugin.php
 */
abstract class AbstractPlugin implements PluginInterface
{
    /**
     * @var FilesystemInterface
     */
    protected $filesystem;

    /**
     * Set the Filesystem object.
     *
     * @param FilesystemInterface $filesystem
     */
    public function setFilesystem(FilesystemInterface $filesystem)
    {
        $this->filesystem = $filesystem;
    }
}