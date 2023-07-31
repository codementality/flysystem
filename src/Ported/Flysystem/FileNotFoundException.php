<?php

namespace Drupal\flysystem\Ported\Flysystem;

use Exception as BaseException;

/**
 * Ported from league/flysystem v1.
 * @see https://raw.githubusercontent.com/thephpleague/flysystem/1.x/src/FileNotFoundException.php.
 */
class FileNotFoundException extends Exception
{
    /**
     * @var string
     */
    protected $path;

    /**
     * Constructor.
     *
     * @param string     $path
     * @param int        $code
     * @param \Exception $previous
     */
    public function __construct($path, $code = 0, BaseException $previous = null)
    {
        $this->path = $path;

        parent::__construct('File not found at path: ' . $this->getPath(), $code, $previous);
    }

    /**
     * Get the path which was not found.
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }
}