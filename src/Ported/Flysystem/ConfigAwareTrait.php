<?php

namespace Drupal\flysystem\Ported\Flysystem;

use Drupal\flysystem\Ported\Flysystem\Config;
/**
 * Ported from league/flysystem v1
 * @see https://raw.githubusercontent.com/thephpleague/flysystem/1.x/src/ConfigAwareTrait.php.
 */
trait ConfigAwareTrait
{
    /**
     * @var \Drupal\flysystem\Ported\Flysystem\Config
     */
    protected $config;

    /**
     * Set the config.
     *
     * @param Config|array|null $config
     */
    protected function setConfig($config)
    {
        $this->config = $config ? Util::ensureConfig($config) : new Config;
    }

    /**
     * Get the Config.
     *
     * @return Config config object
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Convert a config array to a Config object with the correct fallback.
     *
     * @param array $config
     *
     * @return \Drupal\flysystem\Ported\Flysystem\Config
     */
    protected function prepareConfig(array $config)
    {
        $config = new Config($config);
        $config->setFallback($this->getConfig());

        return $config;
    }
}