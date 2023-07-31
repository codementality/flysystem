<?php

namespace Drupal\flysystem\Ported\Flysystem\Adapter\Polyfill;

/**
 * Ported from league/flysystem v1.
 * @see https://raw.githubusercontent.com/thephpleague/flysystem/1.x/src/Adapter/Polyfill/StreamedReadingTrait.php.
 *
 * A helper for adapters that only handle strings to provide read streams.
 */
trait StreamedReadingTrait
{
    /**
     * Reads a file as a stream.
     *
     * @param string $path
     *
     * @return array|false
     *
     * @see Drupal\flysystem\Ported\Flysystem\ReadingInterface::readStream()
     */
    public function readStream($path)
    {
        if ( ! $data = $this->read($path)) {
            return false;
        }

        $stream = fopen('php://temp', 'w+b');
        fwrite($stream, $data['contents']);
        rewind($stream);
        $data['stream'] = $stream;
        unset($data['contents']);

        return $data;
    }

    /**
     * Reads a file.
     *
     * @param string $path
     *
     * @return array|false
     *
     * @see Drupal\flysystem\Ported\Flysystem\ReadingInterface::read()
     */
    abstract public function read($path);
}