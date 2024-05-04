<?php

namespace Drupal\flysystem_local\Plugin\flysystem\Adapter;

use Drupal\Component\Utility\UrlHelper;
// This is included for some constants in this service related to file and
// directory permissions.
use Drupal\Core\File\FileSystem;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\flysystem\Adapters\FlysystemMissingAdapter;
use Drupal\flysystem\Plugin\Adapter\AdapterPluginBase;
use Drupal\flysystem\Plugin\Trait\FlysystemUrlTrait;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Drupal plugin for the "Local" Flysystem adapter.
 *
 * @FlysystemAdapter(
 *   id = "local",
 *   label = "Local Adapter",
 *   description = "Flysystem Local Adapter"
 * )
 */
class LocalAdapter extends AdapterPluginBase implements PluginFormInterface {

  use FlysystemUrlTrait {
    getExternalUrl as getDownloadlUrl;
  }

  /**
   * The permissions to create directories with.
   *
   * @var int
   */
  protected $directoryPerm;

  /**
   * Whether the root is in the public path.
   *
   * @var bool
   */
  protected $isPublic;

  /**
   * The root of the local adapter.
   *
   * @var string
   */
  protected $root;

  /**
   * Whether the root exists and is readable.
   *
   * @var bool
   */
  protected $rootExists;

  /**
   * Constructs a Local object.
   *
   * @param string $root
   *   The of the adapter's filesystem.
   * @param bool $is_public
   *   (optional) Whether this is a public file system. Defaults to false.
   * @param int $directory_permission
   *   (optional) The permissions to create directories with.
   */
  public function __construct($root, $is_public = FALSE, $directory_permission = FileSystem::CHMOD_DIRECTORY) {
    $this->isPublic = $is_public;
    $this->root = $root;
    $this->directoryPerm = $directory_permission;
    $this->rootExists = $this->ensureDirectory();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration['root'],
      !empty($configuration['public']),
      $container->get('settings')->get('file_chmod_directory', FileSystem::CHMOD_DIRECTORY)
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getAdapter() {
    return $this->rootExists ? new LocalFilesystemAdapter($this->root) : new FlysystemMissingAdapter();
  }

  /**
   * {@inheritdoc}
   */
  public function getExternalUrl($uri, $isSecureRequest = FALSE) {
    if ($this->isPublic === FALSE) {
      return $this->getDownloadlUrl($uri);
    }

    $path = str_replace('\\', '/', $this->root . '/' . $this->getTarget($uri));

    return $GLOBALS['base_url'] . '/' . UrlHelper::encodePath($path);
  }

  /**
   * {@inheritdoc}
   */
  public function ensure($force = FALSE) {
    if (!$this->rootExists) {
      return [
        [
          'severity' => RfcLogLevel::ERROR,
          'message' => 'The %root directory either does not exist or is not readable and attempts to create it have failed.',
          'context' => ['%root' => $this->root],
        ],
      ];
    }

    if (!$this->writeHtaccess($force)) {
      return [
        [
          'severity' => RfcLogLevel::ERROR,
          'message' => 'See <a href="@url">@url</a> for information about the recommended .htaccess file which should be added to the %directory directory to help protect against arbitrary code execution.',
          'context' => [
            '%directory' => $this->root,
            '@url' => 'https://www.drupal.org/SA-CORE-2013-003',
          ],
        ],
      ];
    }

    return [
      [
        'severity' => RfcLogLevel::INFO,
        'message' => 'The directory %root exists and is readable.',
        'context' => ['%root' => $this->root],
      ],
    ];
  }

  /**
   * Checks that the directory exists and is readable.
   *
   * This will attempt to create the directory if it doesn't exist.
   *
   * @return bool
   *   True on success, false on failure.
   */
  protected function ensureDirectory() {
    // Go for the success case first.
    if (is_dir($this->root) && is_readable($this->root)) {
      return TRUE;
    }

    if (!file_exists($this->root)) {
      mkdir($this->root, $this->directoryPerm, TRUE);
    }

    if (is_dir($this->root) && chmod($this->root, $this->directoryPerm)) {
      clearstatcache(TRUE, $this->root);
      $this->writeHtaccess(TRUE);
      return TRUE;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   *
   * @todo Complete this method.
   */
  public function getConfiguration() {
    return [];
  }

  /**
   * {@inheritdoc}
   *
   * @todo Complete this method.
   */
  public function setConfiguration($adapterConfig) {
  }

  /**
   * {@inheritdoc}
   *
   * @todo Complete method.
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    return [];
  }

  /**
   * {@inheritdoc}
   *
   * @todo Complete method.
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   *
   * @todo Complete method.
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   *
   * @todo Complete method
   * @todo determine if this needs to be defined on the interface, or
   *   declared as protected or private.
   */
  public function writeHtaccess($force = FALSE) {

  }

}
