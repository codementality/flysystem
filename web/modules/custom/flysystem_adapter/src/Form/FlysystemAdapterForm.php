<?php

declare(strict_types=1);

namespace Drupal\flysystem_adapter\Form;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Component\Utility\Html;
use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Render\Markup;
use Drupal\flysystem_adapter\Entity\FlysystemAdapter;
use Drupal\flysystem_adapter\FlysystemAdapterInterface;
use Drupal\flysystem_adapter\Plugin\FlysystemAdapterConfigPluginManager;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Flysystem adapter form.
 */
class FlysystemAdapterForm extends EntityForm {

  /**
   * The config entity being created/updated by this form.
   *
   * @var \Drupal\flysystem_adapter\FlysystemAdapterInterface
   */
  protected $entity;

  /**
   * The adapter config plugin manager.
   *
   * @var \Drupal\flysystem_adapter\Plugin\FlysystemAdapterConfigPluginManager
   */
  protected $adapterPluginManager;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs a ServerForm object.
   *
   * @param \Drupal\flysystem_adapter\Plugin\FlysystemAdapterConfigPluginManager $adapter_plugin_manager
   *   The backend plugin manager.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   */
  public function __construct(FlysystemAdapterConfigPluginManager $adapter_plugin_manager, MessengerInterface $messenger) {
    $this->adapterPluginManager = $adapter_plugin_manager;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $adapter_plugin_manager = $container->get('plugin.manager.flysystem_adapter_config');
    $messenger = $container->get('messenger');

    return new static($adapter_plugin_manager, $messenger);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    // If the form is being rebuilt, rebuild the entity with the current form
    // values.
    if ($form_state->isRebuilding()) {
      $this->entity = $this->buildEntity($form, $form_state);
    }

    $form = parent::form($form, $form_state);

    /** @var \Drupal\flysystem_adapter\FlysystemAdapterInterface $adapterPlugin */
    $adapterPlugin = $this->getEntity();
  
    // Set the page title according to whether we are creating or editing the
    // adapter plugin
    if ($adapterPlugin->isNew()) {
      $form['#title'] = $this->t('Add Flysytem Adapter');
    }
    else {
      $form['#title'] = $this->t('Edit Flysystem Adapter %label', ['%label' => $adapterPlugin->label()]);
    }

    $this->buildEntityForm($form, $form_state, $adapterPlugin);

    if ($form) {
      $this->buildAdapterConfigForm($form, $form_state, $adapterPlugin);
    }
    return $form;

    $form['adapter_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Select an Adapter type'),
      '#options' => $options,
      '#default_value' => !is_null($adapterPlugin->adapterPluginId()) ? $adapterPlugin->adapterPluginId() : $default,
      '#required' => TRUE,
      '#disabled' => !$adapterPlugin->isNew(),
      '#ajax' => [
        'callback' => [get_class($this), 'buildAjaxAdapterConfigPluginForm'],
        'wrapper' => 'flysystem-adapter-plugin-config-form',
        'method' => 'replace',
        'effect' => 'fade',
      ],
    ];

    $form['adapter_type'] += $descriptions;

    [$options, $descriptions] = $this->getAvailableAdapterPlugins($adapterPlugin);

    $default = count($options) == 1 ? (string) array_key_first($options): NULL;


  }

/**
   * Builds the form for the basic server properties.
   *
   * @param array $form
   *   The current form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   * @param \Drupal\flysystem_adapter\FlysystemAdapterInterface $adapterPlugin
   *   The server that is being created or edited.
   */
  public function buildEntityForm(array &$form, FormStateInterface $form_state, FlysystemAdapterInterface $adapterPlugin) {

    //$form['#attached']['library'][] = 'search_api/drupal.search_api.admin_css';
  
    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Adapter Plugin name'),
      '#description' => $this->t('Enter the displayed name for the adapter plugin.'),
      '#default_value' => $adapterPlugin->label(),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $adapterPlugin->isNew() ? NULL : $adapterPlugin->id(),
      '#maxlength' => 50,
      '#required' => TRUE,
      '#machine_name' => [
        'exists' => '\Drupal\flysystem_adapter\Entity\FlysystemAdapter::load',
        'source' => ['name'],
      ],
      '#disabled' => !$adapterPlugin->isNew(),
    ];

    $form['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled'),
      '#description' => $this->t('Only enabled adapter plugins can be configured for file management.'),
      '#default_value' => $adapterPlugin->status(),
    ];

    $form['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#description' => $this->t('Enter a description for the adapter plugin.'),
      '#default_value' => $adapterPlugin->description(),
    ];

    $adapters = $this->adapterPluginManager->getDefinitions();
    $adapter_options = [];
    $descriptions = [];

    foreach ($adapters as $adapter_id => $definition) {
      $config = $adapter_id === $adapterPlugin->id() ? $adapterPlugin->getAdapterConfig() : [];
      $config['#adapter-plugin'] = $adapterPlugin;
      try {
        /** @var \Drupal\search_api\Backend\BackendInterface $backend */
        $adapter = $this->adapterPluginManager
          ->createInstance($adapter_id, $config);
      }
      catch (PluginException) {
        continue;
      }
      if ($backend->isHidden()) {
        continue;
      }
      $adapter_config_options[$adapter_id] = Utility::escapeHtml($adapter->label());
      $descriptions[$adapter_id]['#description'] = Utility::escapeHtml($adapter->description());
    }
    asort($adapter_config_options, SORT_NATURAL | SORT_FLAG_CASE);

  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {

    $adapterPlugin = $this->getEntity();
    
    /** @var int $result */
    $result = $adapterPlugin->save();
    $this->messenger->addStatus($this->t('The configured Flysystem Adapter was successfully saved.'));

    $message_args = ['%label' => $adapterPlugin->label()];
    $message = '';
    if ($result == SAVED_NEW) {
      $message = $this->t('Created new example %label.', $message_args);
    }
    elseif ($result == SAVED_UPDATED) {
      $message = $this->t('Created new example %label.', $message_args);
    }
    $this->messenger()->addStatus($message);

    //$form_state->setRedirectUrl($$adapterPlugin->toUrl('collection'));
    $form_state->setRedirect('entity.flysystem_adapter.collection');
    return $result;
  }

  /**
   * Handles switching the selected backend plugin.
   *
   * @param array $form
   *   The current form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   *
   * @return array
   *   The part of the form to return as AJAX.
   */
  public static function buildAjaxAdapterConfigPluginForm(array $form, FormStateInterface $form_state) {
    // The work is already done in form(), where we rebuild the entity according
    // to the current form values and then create the adapter plugin
    // configuration form based on that. So we just need to return the relevant
    // part of the form here.
    return $form['adapter_plugin_config'];
  }

  /**
   * Builds the adapter plugin specifc configuration form.
   *
   * @param array $form
   *   The current form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   * @param \Drupal\flysystem_adapter\FlysystemAdapterInterface $adapterPlugin
   *   The server that is being created or edited.
   */
  public function buildAdapterConfigForm(array &$form, FormStateInterface $form_state, FlysystemAdapterInterface $adapterPlugin) {
    $form['adapter_plugin_config'] = [];
    if ($adapterPlugin->hasValidAdapter()) {
      $adapterConfig = $adapterPlugin->adapterPluginConfig();
      $form_state->set('adapter_type', $adapterPlugin->adapterPluginId());
      if ($adapterConfig instanceof PluginFormInterface) {
        // Attach the adapter plugin configuration form.
        $adapter_plugin_form_state = SubformState::createForSubform($form['adapter_plugin_config'], $form, $form_state);
        $form['adapter_plugin_config'] = $adapterConfig->buildConfigurationForm($form['adapter_plugin_config'], $adapter_plugin_form_state);

        // Modify the adapter plugin configuration container element.
        $form['adapter_plugin_config']['#type'] = 'details';
        $form['adapter_plugin_config']['#title'] = $this->t('Configure %plugin backend', ['%plugin' => $adapterPlugin->label()]);
        $form['adapter_plugin_config']['#open'] = TRUE;
      }
    }
    // Only notify the user of a missing adapter plugin if we're editing an
    // existing adapter.
    elseif (!$adapterPlugin->isNew()) {
      $this->messenger->addError($this->t('The Flysystem adapter plugin is missing or invalid.'));
      return;
    }
    $form['adapter_plugin_config'] += [
      '#type' => 'container',
    ];
    $form['adapter_plugin_config']['#attributes']['id'] = 'flysystem-adapter-plugin-config-form';
    $form['adapter_plugin_config']['#tree'] = TRUE;
  }

  /**
   * Returns list of available adapter plugins for configuration.
   *
   * @param \Drupal\flysystem_adapter\FlysystemAdapterInterface $adapterPlugin
   *   This adapter plugin.
   *
   * @return array
   *   Array with list of adapters and adapter descriptions, keyed by
   *   adapter_id.
   */
  private function getAvailableAdapterPlugins($adapterPlugin) {
    $type = $this->adapterPluginManager;
    $plugin_definitions = $type->getDefinitions();

    $options = [];
    $descriptions = [];
    foreach ($plugin_definitions as $adapter_id => $definition) {
      $config = $adapter_id === $adapterPlugin->adapterPluginId() ? $adapterPlugin->adapterPluginConfig() : [];
      $options[$adapter_id] = (string) $definition['label'];
      $config['#adapter-plugin'] = $adapterPlugin;
      try {
        /** @var \Drupal\flysystem_adapter\FlysystemAdapterInterface $adapter */
        $adapter = $type
          ->createInstance($adapter_id, $config);
      }
      catch (PluginException) {
        continue;
      }
      $options[$adapter_id] = $this->escapeHtml($adapterPlugin->label());
      $descriptions[$adapter_id]['#description'] = $this->escapeHtml($adapterPlugin->description());
    }
    asort($options, SORT_NATURAL | SORT_FLAG_CASE);
    return [$options, $descriptions];
  }

  /**
   * Escapes HTML special characters in plain text, if necessary.
   *
   * @param string|\Drupal\Component\Render\MarkupInterface $text
   *   The text to escape.
   *
   * @return \Drupal\Component\Render\MarkupInterface
   *   If a markup object was passed as $text, it is returned as-is. Otherwise,
   *   the text is escaped and returned
   */
  private function escapeHtml($text) {
    if ($text instanceof MarkupInterface) {
      return $text;
    }
    return Markup::create(Html::escape((string) $text));
  }


}
