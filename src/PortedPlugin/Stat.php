<?php

namespace Drupal\flysystem\PortedPlugin;

use Drupal\flysystem\Ported\Flysystem\AdapterInterface;
use Drupal\flysystem\FlysystemStreamWrapper;
use Drupal\flysystem\Ported\PosixUid;
use Drupal\flysystem\Ported\Uid;
use Drupal\flysystem\PortedPlugin\AbstractPlugin;

/**
 * Ported from twistor/flysystem-stream-wrapper. 
 * @see https://raw.githubusercontent.com/twistor/flysystem-stream-wrapper/master/src/Flysystem/Plugin/Stat.php.
 */
class Stat extends AbstractPlugin
{
    /**
     * Default return value of url_stat().
     *
     * @var array
     */
    protected static $defaultMeta = [
        'dev' => 0,
        'ino' => 0,
        'mode' => 0,
        'nlink' => 0,
        'uid' => 0,
        'gid' => 0,
        'rdev' => 0,
        'size' => 0,
        'atime' => 0,
        'mtime' => 0,
        'ctime' => 0,
        'blksize' => -1,
        'blocks' => -1,
    ];

    /**
     * Permission map.
     *
     * @var array
     */
    protected $permissions;

    /**
     * Required metadata.
     *
     * @var array
     */
    protected $required;

    /**
     * @var \Drupal\flysystem\Ported\Uid
     */
    protected $uid;

    /**
     * Constructs a Stat object.
     *
     * @param array $permissions An array of permissions.
     * @param array $metadata    The default required metadata.
     */
    public function __construct(array $permissions, array $metadata)
    {
        $this->permissions = $permissions;
        $this->required = array_combine($metadata, $metadata);
        $this->uid = \extension_loaded('posix') ? new PosixUid() : new Uid();
    }

    /**
     * @inheritdoc
     */
    public function getMethod()
    {
        return 'stat';
    }

    /**
     * Emulates stat().
     *
     * @param string $path
     * @param int $flags
     *
     * @return array Output similar to stat().
     *
     * @throws \Drupal\flysystem\Ported\Flysystem\FileNotFoundException
     *
     * @see stat()
     */
    public function handle($path, $flags)
    {
        if ($path === '') {
            return $this->mergeMeta(['type' => 'dir', 'visibility' => AdapterInterface::VISIBILITY_PUBLIC]);
        }

        $ignore = $flags & FlysystemStreamWrapper::STREAM_URL_IGNORE_SIZE ? ['size'] : [];

        $metadata = $this->getWithMetadata($path, $ignore);

        // It's possible for getMetadata() to fail even if a file exists.
        if (empty($metadata)) {
            return static::$defaultMeta;
        }

        return $this->mergeMeta($metadata + ['visibility' => AdapterInterface::VISIBILITY_PUBLIC]);
    }

    /**
     * Returns metadata.
     *
     * @param string $path The path to get metadata for.
     * @param array $ignore Metadata to ignore.
     *
     * @return array The metadata as returned by Filesystem::getMetadata().
     *
     * @throws \Drupal\flysystem\Ported\Flysystem\FileNotFoundException
     *
     * @see \Drupal\flysystem\Ported\Flysystem\Filesystem::getMetadata()
     */
    protected function getWithMetadata($path, array $ignore)
    {
        $metadata = $this->filesystem->getMetadata($path);

        if (empty($metadata)) {
            return [];
        }

        $keys = array_diff($this->required, array_keys($metadata), $ignore);

        foreach ($keys as $key) {
            $method = 'get' . ucfirst($key);

            try {
                $metadata[$key] = $this->filesystem->$method($path);
            } catch (\LogicException $e) {
                // Some adapters don't support certain metadata. For instance,
                // the Dropbox adapter throws exceptions when calling
                // getVisibility(). Remove the required key so we don't keep
                // calling it.
                unset($this->required[$key]);
            }
        }

        return $metadata;
    }

    /**
     * Merges the available metadata from Filesystem::getMetadata().
     *
     * @param array $metadata The metadata.
     *
     * @return array All metadata with default values filled in.
     */
    protected function mergeMeta(array $metadata)
    {
        $ret = static::$defaultMeta;

        $ret['uid'] = $this->uid->getUid();
        $ret['gid'] = $this->uid->getGid();

        $ret['mode'] = $metadata['type'] === 'dir' ? 040000 : 0100000;
        $ret['mode'] += $this->permissions[$metadata['type']][$metadata['visibility']];

        if (isset($metadata['size'])) {
            $ret['size'] = (int) $metadata['size'];
        }
        if (isset($metadata['timestamp'])) {
            $ret['mtime'] = (int) $metadata['timestamp'];
            $ret['ctime'] = (int) $metadata['timestamp'];
        }

        $ret['atime'] = time();

        return array_merge(array_values($ret), $ret);
    }
}