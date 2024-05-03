<?php

declare(strict_types=1);

namespace Drupal\flysystem_adapter\Plugin\FlysystemAdapterConfig;

use Drupal\flysystem_adapter\Plugin\FlysystemAdapterConfigPluginBase;
use Drupal\flysystem_adapter\Plugin\FlysystemAdapterConfigInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Plugin\ConfigurableInterface;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\UnixVisibility\PortableVisibilityConverter;
use League\Flysystem\Visibility;


/**
 * Plugin implementation of the flysystem_adapter_config.
 *
 * @FlysystemAdapterConfig(
 *   id = "local",
 *   label = @Translation("Flysystem Local"),
 *   description = @Translation("Flysystem Local Filesystem adapter.")
 * )
 */
class LocalAdapter extends FlysystemAdapterConfigPluginBase implements PluginFormInterface, FlysystemAdapterConfigInterface {

  /**
   * The example ID.
   */
  protected $id;

  /**
   * The example label.
   */
  protected $label;

  /**
   * The example description.
   */
  protected $description;

  /**
   * The schema for the configured adapter.
   *
   * @var string
   */
  protected $schema;

  /**
   * Physical location of files managed by this adapter.
   *
   * @var string
   */
  protected $filesLocation;

  /**
   * Public files permissions.
   *
   * @var int
   */
  protected $publicFilePerms = 0640;

  /**
   * Private files permissions.
   *
   * @var int
   */
  protected $privateFilePerms = 0604;

  /**
   * Public directory permissions.
   *
   * @var int
   */
  protected $publicDirPerms = 0740;

  /**
   * Private directory permissions.
   *
   * @var int
   */
  protected $privateDirPerms = 7604;

  /**
   * Write flags, defaults to LOCK_EX.
   *
   * @var int
   */
  protected $fileLockOp = LOCK_EX;

  /**
   * Visibility permissions for this adapter.
   *
   * @var \League\Flysystem\UnixVisibility\VisibilityConverter
   */
  protected $visibility;

  /**
   * Default directory permissions.
   *
   * @var string
   */
  protected $defaultForDirectories = Visibility::PRIVATE;

  /**
   * Link handling.
   *
   * @var int
   */
  protected $linkHandling = LocalFilesystemAdapter::DISALLOW_LINKS;

  /**
   * Plugin manager.
   *
   * @var \Drupal\flysystem_adapter\Plugin\FlysystemAdapterConfigPluginManager
   */
  protected $adapterConfigPluginManager;

  /**
   * Constructs a Flysystem Local Adapter Config object.
   *
   * @param array $configuration
   *   A configuration array containing settings for this backend.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    if (isset($configuration[''])) {
      [$key, $target] = explode(':', $configuration['database'], 2);
      // @todo Can we somehow get the connection in a dependency-injected way?
      $this->adapterConfigPluginManager = \Drupal::service('plugin.manager.flysystem_adapter_config');
      $type = $this->adapterConfigPluginManager(\Drupal::getContainer()->getParameter('container.namespaces')); 
    }
  }

  /**
   * {@inheritdoc}
   */
  public function description(): string {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    return $this->label;
  }

  /**
   * {@inheritdoc}
   */
  public function id(): string {
    return $this->id;
  }

  /**
   * Set Visibility parameters for Adapter.
   *
   * @param int $publicFilePerms
   *   Public file permissions.
   * @param int $privateFilePerms
   *   Private file permissions.
   * @param int $publicDirPerms
   *   Public directory permissions.
   * @param int $privateDirPerms
   *   Private directory permissions.
   */
  public function setVisibility($publicFilePerms = 0640, $privateFilePerms = 0604, $publicDirPerms = 0740, $privateDirPerms = 7604, $defaultForDirectories = Visibility::PRIVATE) {
    $visibility = PortableVisibilityConverter::fromArray([
      'file' => [
        'public' => $publicFilePerms,
        'private' => $privateFilePerms,
      ],
     'dir' => [
        'public' => $publicDirPerms,
        'private' => $privateDirPerms,
      ],
    ]);
    $this->defaultForDirectories = $defaultForDirectories;
    $this->visibility = $visibility;
  }

  /**
   * Get file / directory visibility settings.
   *
   * @return \League\Flysystem\UnixVisibility\VisibilityConverter
   */
  public function getVisibility() {
    return $this->visibility;
  }

  /**
   * Set link handling.
   *
   * @param int $linkHandling
   *   Options are LocalFilesystemAdapter::DISALLOW_LINKS or 
   *   LocalFilesystemAdapter::SKIP_LINKS.
   */
  public function setLinkHandling($linkHandling = LocalFilesystemAdapter::DISALLOW_LINKS) {
    $this->linkHandling = $linkHandling;
  }

  /**
   * Get link handling setting.
   *
   * @return int
   *   Configured default permission for Link handling.
   */
  public function getLinkHandling($linkHandling = LocalFilesystemAdapter::DISALLOW_LINKS) {
    return $this->linkHandling;
  }

  /**
   * Get default directory permissions.
   *
   * @return string
   *   Default directory permissions.
   */
  public function getDefaultDirectoryPermissions() {
    return $this->defaultForDirectories;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    // If this is a new adapter...
    if (!$this->configuration['adapter_type']) {
      $options = [];

      // @todo Write routine to get adapters from plugin manager.
      if (count($options) > 1 || count(reset($options)) > 1) {
        $form['adapter_type'] = [
          '#type' => 'select',
          '#title' => $this->t('Adapter Type'),
          '#description' => $this->t('Select the Flysystem Adapter Type to use to configure this adapter. Cannot be changed after creation.'),
          '#options' => $options,
          '#default_value' => NULL,
          '#required' => TRUE,
        ];
      }
      else {
        $form['adapter_type'] = [
        '#type' => 'value',
        '#value' => $this->configuration['adapter_type']
        ];
      }
    }
    // If this is an existing adapter...
    else {
      $form = [
        'adapter_type' => [
        '#type' => 'value',
        '#title' => $this->t('Adapter Type'),
        '#value' => $this->configuration['adapter_type'],
        ],
      ];
    }

    $form['schema'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Adapter schema'),
      '#description' => $this->t('Schema for files managed with this adapter.'),
      '#default_value' => $this->configuration['schema'],
    ];
  
    $form['files_location'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Files storage location'),
      '#description' => $this->t('File system location, relative to the Drupal root, where files managed by this adapter are located.'),
      '#default_value' => $this->configuration['files_location'],
    ];
  
    $form['visibility']['file']['public'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Public file permissions'),
      '#description' => $this->t('Default file permissions for public files'),
      '#default_value' => $this->configuration['visibility']['file']['public'],
    ];

    $form['visibility']['file']['private'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Private file permissions'),
      '#description' => $this->t('Default file permissions for private files'),
      '#default_value' => $this->configuration['visibility']['file']['private'],
    ];
  
    $form['visibility']['directory']['public'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Public directory permissions'),
      '#description' => $this->t('Default directory permissions for public directories'),
      '#default_value' => $this->configuration['visibility']['directory']['public'],
    ];

    $form['visibility']['directory']['private'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Private directory permissions'),
      '#description' => $this->t('Default directory permissions for private directories'),
      '#default_value' => $this->configuration['visibility']['directory']['private'],
    ];

    $form['default_for_directories'] = [
      '#type' => 'select',
      '#title' => $this->t('Default configuration for directories'),
      '#description' => $this->t('Default configuration for directories (public or private, defaults to private'),
      '#default_value' => $this->configuration['default_for_directories'],
      '#options' => [
        'private' => $this->t('Private'),
        'public' => $this->t('Public'),
      ],
    ];
  
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    if ($this instanceof ConfigurableInterface) {
        $this->setConfiguration($form_state->getValues());
      }
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // @todo Write validation for Adapter type, schema, and look at validating permissions.
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    parent::setConfiguration($configuration);

    if ($this->adapterPlugin && $this->adapterPlugin->adapterPluginConfig() !== $configuration) {
      $this->adapterPlugin->setAdapterPluginConfig($configuration);
    }
  }

  /**
   * Retrieves the adapter config plugin manager.
   *
   * @return \Drupal\flysystem_adapter\Plugin\FlysystemAdapterConfigPluginManager
   *   The adapter config plugin manager.
   */
  public function getAdapterConfigPluginManager() {
    return $this->adapterConfigPluginManager ?: \Drupal::service('plugin.manager.flysystem_adapter_config');
  }

}
