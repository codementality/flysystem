<?php

namespace Drupal\flysystem\Form;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Render\MarkupInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Utility\Error;
use Drupal\flysystem\Entity\FlysystemAdapterEntityInterface;
use Drupal\flysystem\Plugin\FlysystemAdapterPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base form for configuring Flysystem Adapter Plugins.
 */
class FlysystemAdapterEntityForm extends EntityForm {

  /**
   * The Adapter plugin manager.
   *
   * @var \Drupal\flysystem\Plugin\FlysystemAdapterPluginManager
   */
  protected $flysystemAdapterPluginManager;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs an AdapterEntityForm object.
   *
   * @param \Drupal\flysystem\Plugin\FlysystemAdapterPluginManager $adapter_plugin_manager
   *   Flysystem Adapter plugin manager.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(FlysystemAdapterPluginManager $adapter_plugin_manager, MessengerInterface $messenger) {
    $this->flysystemAdapterPluginManager = $adapter_plugin_manager;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $adapter_plugin_manager = $container->get('plugin.manager.flysystem_adapter');
    $messenger = $container->get('messenger');

    return new static($adapter_plugin_manager, $messenger);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {

    if ($form_state->isRebuilding()) {
      $this->entity = $this->buildEntity($form, $form_state);
    }

    $form = parent::form($form, $form_state);

    /** @var \Drupal\flysystem\Entity\FlysystemAdapterEntityInterface $flysystemAdapter */
    $flysystemAdapter = $this->getEntity();

    // Set the page title according to whether we are creating or editing the
    // adapter.
    if ($flysystemAdapter->isNew()) {
      $form['#title'] = $this->t('Add Flysystem Adapter');
    }
    else {
      $form['#title'] = $this->t('Edit Flysystem Adapter %label', ['%label' => $flysystemAdapter->label()]);
    }

    $this->buildEntityForm($form, $form_state, $flysystemAdapter);

    if ($form) {
      $this->buildAdapterConfigForm($form, $form_state, $flysystemAdapter);
    }

    return $form;
  }

  /**
   * Builds the form for the basic adapter properties.
   *
   * @param array $form
   *   Current form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   * @param \Drupal\flysystem\Entity\FlysystemAdapterEntityInterface $flysystemAdapter
   *   The adapter being created or modified.
   */
  public function buildEntityForm(array &$form, FormStateInterface $form_state, FlysystemAdapterEntityInterface $flysystemAdapter) {

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $flysystemAdapter->label(),
      '#description' => $this->t("Label for the adapter configuration."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $flysystemAdapter->id(),
      '#machine_name' => [
        'exists' => '\Drupal\flysystem\Entity\FlysystemAdapterEntity::load',
      ],
      '#disabled' => !$flysystemAdapter->isNew(),
    ];

    $form['description'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Description'),
      '#maxlength' => 255,
      '#default_value' => $flysystemAdapter->getDescription(),
      '#description' => $this->t("An expanded description of this adapter."),
    ];

    /* You will need additional form elements for your custom properties. */

    $availableAdapterPlugins = $this->flysystemAdapterPluginManager->getDefinitions();
    $adapter_options = [];
    $descriptions = [];
    foreach ($availableAdapterPlugins as $plugin_id => $definition) {
      $config = $plugin_id === $flysystemAdapter->getAdapterId() ? $flysystemAdapter->getAdapterConfig() : [];
      $config['#adapter-plugin'] = $flysystemAdapter;
      try {
        /** @var \Drupal\flysystem\Entity\FlysystemAdapterEntityInterface $adapter */
        $adapterPlugin = $this->flysystemAdapterPluginManager->createInstance($plugin_id, $config);
      }
      catch (PluginException) {
        continue;
      }
      $adapter_options[$plugin_id] = $this->escapeHtml($adapter->label());
      $descriptions[$plugin_id]['#description'] = $this->escapeHtml($adapterPlugin->getDescription());
    }
    asort($adapter_options, SORT_NATURAL | SORT_FLAG_CASE);

    if ($adapter_options) {
      if (count($adapter_options) == 1) {
        $flysystemAdapter->set('options', key($adapter_options));
      }

      $form['adapter-plugins'] = [
        '#type' => 'radios',
        '#title' => $this->t('Adapter'),
        '#description' => $this->t('Choose an adapter Plugin to use.'),
        '#options' => $adapter_options,
        '#default_value' => $flysystemAdapter->getAdapterId(),
        '#required' => TRUE,
        '#disabled' => !$flysystemAdapter->isNew(),
        '#ajax' => [
          'callback' => [get_class($this), 'buildAjaxAdapterConfigForm'],
          'wrapper' => 'flysystem-adapter-config-form',
          'method' => 'replace',
          'effect' => 'fade',
        ],
      ];
      $form['adapter-plugins'] += $descriptions;
    }
    else {
      // @todo Update and uncomment this after documenting plugin information.
      // $url = 'https://www.drupal.org/docs/8/modules/search-api/getting-started/server-backends-and-features';
      // $args[':url'] = Url::fromUri($url)->toString();
      $args[':url'] = "#";
      $error = $this->t('There are no Flysystem adapter plugins available to use.  Please install a <a href=":url">module that provides a Flysystem adapter to proceed.', $args);
      $this->messenger->addError($error);
      $form = [];
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    if ($form === []) {
      return [];
    }

    return parent::actions($form, $form_state);
  }

  /**
   * Builds the adapter-specific configuration form.
   *
   * @param array $form
   *   The current form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   * @param \Drupal\flysystem\Entity\FlysystemAdapterEntityInterface $flysystemAdapter
   *   The adapter that is being created or modified.
   */
  public function buildAdapterConfigForm(array &$form, FormStateInterface $form_state, FlysystemAdapterEntityInterface $flysystemAdapter) {

    $form['adapter_config'] = [];
    $adapter = $flysystemAdapter->getAdapter();
    $form_state->set('adapter', $adapter->getPluginId());
    if ($adapter instanceof PluginFormInterface) {
      if ($form_state->isRebuilding()) {
        $this->messenger->addWarning($this->t('Please configure the selected adapter.'));
      }

      // Attach the adapter plugin configuration form.
      $adapter_form_state = SubformState::createForSubform($form['adapter_config'], $form, $form_state);
      $form['adapter_config'] = $adapter->buildConfigurationForm($form['adapter_config'], $adapter_form_state);

      // Modify the adapter plugin configuration container element.
      $form['adapter_config']['#type'] = 'details';
      $form['adapter_config']['#title'] = $this->t('Configure %plugin adapter', ['#plugin' => $adapter->label()]);
      $form['adapter_config']['#open'] = TRUE;
    }
    elseif (!$flysystemAdapter->isNew()) {
      $this->messenger->addError($this->t('The adapter plugin is missing or invalid.'));
      return;
    }
    $form['adapter_config'] += [
      '#type' => 'container',
    ];
    $form['adapter_config']['#attributes']['id'] = 'flysystem-adapter-config-form';
    $form['adapter_config']['#tree'] = TRUE;
  }

  /**
   * Handles switching the selected adapter plugin.
   *
   * @param array $form
   *   The current form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   *
   * @return array
   *   The part of the form to return as AJAX.
   */
  public static function buildAjaxAdapterConfigForm(array $form, FormStateInterface $form_state) {
    // The work is already done in form(), where we rebuild the entity according
    // to the current form values and then create the backend configuration form
    // based on that. So we just need to return the relevant part of the form
    // here.
    return $form['adapter_config'];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    /** @var \Drupal\flysystem\Entity\FlysystemAdapterEntityInterface $flysystemAdapter */
    $flysystemAdapter = $this->getEntity();

    // Check if the backend plugin changed.
    $adapter_id = $flysystemAdapter->getAdapterId();
    if ($adapter_id != $form_state->get('adapter')) {
      // This can only happen during initial adapter creation, since we don't
      // allow switching the adapter afterwards. The user has selected a
      // different adapter, so any values entered for the other adapter should
      // be discarded.
      $input = &$form_state->getUserInput();
      $input['adapter_config'] = [];
      $new_adapter = $this->flysystemAdapterPluginManager->createInstance($form_state->getValue('adapter'));
      if ($new_adapter instanceof PluginFormInterface) {
        $form_state->setRebuild();
      }
    }
    // Check before loading the backend plugin so we don't throw an exception.
    else {
      if ($flysystemAdapter instanceof PluginFormInterface) {
        $adapter_form_state = SubformState::createForSubform($form['adapter_config'], $form, $form_state);
        $flysystemAdapter->validateConfigurationForm($form['adapter_config'], $adapter_form_state);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $flysystemAdapter = $this->getEntity();
    if ($flysystemAdapter instanceof PluginFormInterface) {
      $adapter_form_state = SubformState::createForSubform($form['adapter_config'], $form, $form_state);
      $flysystemAdapter->submitConfigurationForm($form['adapter_config'], $adapter_form_state);
    }

    return $flysystemAdapter;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    // Only save the adapter if the form doesn't need to be rebuilt.
    if (!$form_state->isRebuilding()) {
      try {
        $flysystemAdapter = $this->getEntity();
        $return = $flysystemAdapter->save();
        $this->messenger->addStatus($this->t('The adapter was successfully saved.'));
        $form_state->setRedirect('entity.flysystem_adapter_entity.canonical', ['search_api_server' => $flysystemAdapter->id()]);
        return $return;
      }
      catch (EntityStorageException $e) {
        $form_state->setRebuild();

        $message = '%type: @message in %function (line %line of %file).';
        $variables = Error::decodeException($e);
        $this->getLogger('flysystem')->error($message, $variables);

        $this->messenger->addError($this->t('The flysystem adapter could not be saved.'));
      }
    }
    return 0;
  }

  /**
   * Escapes HTML special characters in plain text, if necessary.
   *
   * @param string|\Drupal\Component\Render\MarkupInterface $text
   *   The text to escape.
   *
   * @return \Drupal\Component\Render\MarkupInterface|string
   *   If a markup object was passed as $text, it is returned as-is. Otherwise,
   *   the text is escaped and returned
   */
  protected function escapeHtml(string|MarkupInterface $text): MarkupInterface|string {
    if ($text instanceof MarkupInterface) {
      return $text;
    }

    return Markup::create(Html::escape((string) $text));
  }

}
