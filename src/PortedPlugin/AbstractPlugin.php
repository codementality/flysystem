<?php

namespace Drupal\flysystem\PortedPlugin;

use Drupal\flysystem\Ported\Flysystem\Config;
use Drupal\flysystem\Ported\Flysystem\Plugin\AbstractPlugin as FlysystemPlugin;

/**
 * Ported from twistor/flysystem-stream-wrapper. 
 * @see https://raw.githubusercontent.com/twistor/flysystem-stream-wrapper/master/src/Flysystem/Plugin/AbstractPlugin.php.
 */
abstract class AbstractPlugin extends FlysystemPlugin
{
    /**
     * @var \Drupal\flysystem\Ported\Flysystem
     */
    protected $filesystem;

    protected function defaultConfig()
    {
        $config = new Config();
        $config->setFallback($this->filesystem->getConfig());

        return $config;
    }
}