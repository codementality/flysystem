<?php

namespace Drupal\flysystem\Ported\Flysystem\Adapter\Polyfill;

use Drupal\flysystem\Ported\Flysystem\Config;

/**
 * Ported from league/flysystem v1.
 * @see https://raw.githubusercontent.com/thephpleague/flysystem/1.x/src/Adapter/Polyfill/StreamedCopyTrait.php.
 */
trait StreamedCopyTrait
{
    /**
     * Copy a file.
     *
     * @param string $path
     * @param string $newpath
     *
     * @return bool
     */
    public function copy($path, $newpath)
    {
        $response = $this->readStream($path);

        if ($response === false || ! is_resource($response['stream'])) {
            return false;
        }

        $result = $this->writeStream($newpath, $response['stream'], new Config());

        if ($result !== false && is_resource($response['stream'])) {
            fclose($response['stream']);
        }

        return $result !== false;
    }

    // Required abstract method

    /**
     * @param string $path
     *
     * @return resource
     */
    abstract public function readStream($path);

    /**
     * @param string   $path
     * @param resource $resource
     * @param Config   $config
     *
     * @return resource
     */
    abstract public function writeStream($path, $resource, Config $config);
}