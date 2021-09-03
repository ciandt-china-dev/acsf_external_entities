<?php

namespace Drupal\acsf_external_entities\Plugin\ExternalEntities\StorageClient;

use Drupal\acsf_external_entities\AcsfQueryTrait;
use Drupal\acsf_external_entities\ApiClient\AcsfApiClientInterface;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\external_entities\ExternalEntityInterface;
use Drupal\external_entities\Plugin\PluginFormTrait;
use Drupal\external_entities\ResponseDecoder\ResponseDecoderFactoryInterface;
use Drupal\external_entities\StorageClient\ExternalEntityStorageClientBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * External entities storage client based on a ACSF API.
 *
 * @ExternalEntityStorageClient(
 *   id = "acsf",
 *   label = @Translation("Acquia Cloud Site Factory"),
 *   description = @Translation("Retrieves external entities from a ACSF API.")
 * )
 */
class Acsf extends ExternalEntityStorageClientBase implements PluginFormInterface {

  use PluginFormTrait;

  use AcsfQueryTrait;

  /**
   * The acsf api client.
   *
   * @var \Drupal\acsf_external_entities\ApiClient\AcsfApiClientInterface|null
   */
  protected $acsfApiClient = NULL;

  /**
   * The acsf api client manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $acsfApiClientManager;

  /**
   * @var \Psr\Log\LoggerInterface
   */
  private $logger;

  /**
   * Constructs a Rest object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   * @param \Drupal\external_entities\ResponseDecoder\ResponseDecoderFactoryInterface $response_decoder_factory
   *   The response decoder factory service.
   * @param \Drupal\Component\Plugin\PluginManagerInterface $acsf_api_client_manager
   *   The acsf api client manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, TranslationInterface $string_translation, ResponseDecoderFactoryInterface $response_decoder_factory, PluginManagerInterface $acsf_api_client_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $string_translation, $response_decoder_factory);
    $this->logger = \Drupal::logger('acsf_external_entities');
    $this->acsfApiClientManager = $acsf_api_client_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('string_translation'),
      $container->get('external_entities.response_decoder_factory'),
      $container->get('plugin.manager.acsf_external_entities.acsf_api_client')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'acsf_api_client' => NULL,
      'endpoint' => NULL,
      'api' => [
        'username' => NULL,
        'password' => NULL,
      ],
      'pager' => [
        'default_limit' => 100,
      ],
    ] + parent::defaultConfiguration();
  }

  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    // ACSF api client.
    $acsf_api_clients = array_map(function ($definition) {
      return $definition['label'] ?? $definition['id'];
    }, $this->acsfApiClientManager->getDefinitions());
    $form['acsf_api_client'] = [
      '#type' => 'radios',
      '#title' => $this->t('Acsf Api Client'),
      '#options' => $acsf_api_clients,
      '#required' => TRUE,
      '#default_value' => $this->getConfiguration()['acsf_api_client'] ?? NULL,
    ];

    // Endpoint.
    $endpoint_overwritten = $this->getApiEndpoint() !== $this->getApiEndpoint(FALSE);
    $form['endpoint'] = [
      '#type' => 'textfield',
      '#title' => $endpoint_overwritten ? $this->t('Endpoint (overwritten)') : $this->t('Endpoint'),
      '#required' => TRUE,
      '#disabled' => $endpoint_overwritten,
      '#default_value' => $this->getApiEndpoint(FALSE),
    ];

    // API.
    $form['api'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Api settings'),
    ];

    $username_overwritten = $this->getApiUsername() !== $this->getApiUsername(FALSE);
    $form['api']['username'] = [
      '#type' => 'textfield',
      '#title' => $username_overwritten ? $this->t('Username (overwritten)') : $this->t('Username'),
      '#description' => $this->t('The HTTP username for the API Basic Auth.'),
      '#disabled' => $username_overwritten,
      '#default_value' => $this->getApiUsername(FALSE),
    ];

    $password_overwritten = $this->getApiPassword() !== $this->getApiPassword(FALSE);
    $form['api']['password'] = [
      '#type' => 'textfield',
      '#title' => $password_overwritten ? $this->t('Password (overwritten)') : $this->t('Password'),
      '#description' => $this->t('The HTTP password for the API Basic Auth.'),
      '#disabled' => $password_overwritten,
      '#default_value' => $this->getApiPassword(FALSE),
    ];

    // Pager.
    $form['pager'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Pager settings'),
    ];

    $form['pager']['default_limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Default number of items per page'),
      '#default_value' => $this->getConfiguration()['pager']['default_limit'] ?? NULL,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $form_state->setValue('endpoint', rtrim($form_state->getValue('endpoint'), '/'));
    $this->setConfiguration($form_state->getValues());
  }

  /**
   * {@inheritdoc}
   */
  public function query(array $parameters = [], array $sorts = [], $start = NULL, $length = NULL) {
    // Return all data if the arguments all empty.
    if (!$parameters && !$sorts && !$start && !$length) {
      $results = $this->getAcsfApiClient()->requestAll();
      return array_column($results, NULL, $this->getAcsfApiClient()->getIdKey());
    }

    // Handle paging query.
    $start = $start ?: 0;
    $limit = $length ?: $this->getConfiguration()['pager']['default_limit'];
    $page = $start / $limit + 1;

    // Get data.
    if (count($parameters) || count($sorts)) {
      $has_parameters_sorts = TRUE;
      $results = $this->getAcsfApiClient()->requestAll();
    }
    else {
      $has_parameters_sorts = FALSE;
      $results = $this->getAcsfApiClient()->requestMultiple($limit, $page);
    }

    // Use id_key's value for array key.
    $results = array_column($results, NULL, $this->getAcsfApiClient()->getIdKey());

    // Handle parameters & sorts.
    if ($has_parameters_sorts) {
      // Handle parameters.
      foreach ($parameters as $parameter) {
        $field = $parameter['field'] ?? NULL;
        $value = $parameter['value'] ?? NULL;
        $operator = $parameter['operator'] ?? NULL;

        if (is_null($field) || is_null($value)) {
          continue;
        }

        $origin_field = $this->getFieldMapping($field);

        $results = array_filter($results, function ($data) use ($origin_field, $value, $operator) {
          return $this->queryOperate($data, $origin_field, $value, $operator);
        });
      }

      // Handle sorts.
      uasort($results, function ($data1, $data2) use ($sorts) {
        $return = 0;

        foreach ($sorts as $sort) {
          $field = $sort['field'] ?? NULL;
          $direction = $sort['direction'] ?? 'ASC';

          if (!$field) {
            continue;
          }

          $origin_field = $this->getFieldMapping($field);

          $return = $this->querySort($data1, $data2, $origin_field, $direction);

          // Stop loop when the return isn't zero.
          if ($return !== 0) {
            break;
          }
        }

        return $return;
      });


      // Handle paging.
      $results = array_slice($results, $start, $limit, TRUE);
    }

    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function loadMultiple(array $ids = NULL) {
    $data = [];

    if (!empty($ids) && is_array($ids)) {
      foreach ($ids as $id) {
        $data[$id] = $this->load($id);
      }
    }
    elseif (is_null($ids)) {
      $data = $this->getAcsfApiClient()->requestAll();
      // Use id_key's value for array key.
      $data = array_column($data, NULL, $this->getAcsfApiClient()->getIdKey());
    }

    return $data;
  }

  /**
   * Loads one entity.
   *
   * @param mixed $id
   *   The ID of the entity to load.
   *
   * @return array|null
   *   A raw data array, NULL if no data returned.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function load($id) {
    $origin_id_key = $this->getAcsfApiClient()->getOriginIdKey();
    $id_key = $this->getAcsfApiClient()->getIdKey();

    // Use query if the id key not is origin id key.
    if ($origin_id_key !== $id_key) {
      $data = $this->query([
        [
          'field' => $id_key,
          'value' => $id,
          'operator' => '='
        ]
      ]);
      $data = reset($data);
    }
    else {
      $data = $this->getAcsfApiClient()->request($id);
    }

    return $data ?: NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function save(ExternalEntityInterface $entity) {
    if ($entity->isNew()) {
      return $this->getAcsfApiClient()->add($entity);
    }
    return $this->getAcsfApiClient()->update($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function delete(ExternalEntityInterface $entity) {
    return $this->getAcsfApiClient()->delete($entity);
  }

  /**
   * Get api endpoint.
   *
   * @param bool $override
   *
   * @return mixed|null
   */
  protected function getApiEndpoint(bool $override = TRUE) {
    $endpoint = $this->getConfiguration()['endpoint'] ?? NULL;

    if ($override) {
      $endpoint = Settings::get('acsf', [])['api']['endpoint'] ?? $endpoint;
    }

    return $endpoint;
  }

  /**
   * Get api username.
   *
   * @param bool $override
   *
   * @return mixed|null
   */
  protected function getApiUsername(bool $override = TRUE) {
    $username = $this->getConfiguration()['api']['username'] ?? NULL;

    if ($override) {
      $username = Settings::get('acsf', [])['api']['username'] ?? $username;
    }

    return $username;
  }

  /**
   * Get api password.
   *
   * @param bool $override
   *
   * @return mixed|null
   */
  protected function getApiPassword(bool $override = TRUE) {
    $password = $this->getConfiguration()['api']['password'] ?? NULL;

    if ($override) {
      $password = Settings::get('acsf', [])['api']['password'] ?? $password;
    }

    return $password;
  }

  /**
   * Get acsf api client.
   *
   * @return \Drupal\acsf_external_entities\ApiClient\AcsfApiClientInterface|null
   *   The acsf api client.
   */
  protected function getAcsfApiClient() {
    if (isset($this->getConfiguration()['acsf_api_client']) && !$this->acsfApiClient) {
      $this->acsfApiClient = $this->acsfApiClientManager->createInstance($this->getConfiguration()['acsf_api_client']);
      if ($this->acsfApiClient instanceof AcsfApiClientInterface) {
        $this->acsfApiClient
          ->setEndpoint($this->getApiEndpoint())
          ->setUsername($this->getApiUsername())
          ->setPassword($this->getApiPassword())
          ->setIdKey($this->getFieldMapping('id'))
          ->setPersistentCacheMaxAge($this->getPersistentCacheMaxAge());
      }
    }
    return $this->acsfApiClient;
  }

  /**
   * Get the field mapping.
   *
   * @param $field_name
   *   The field name.
   *
   * @return string
   */
  protected function getFieldMapping(string $field_name) {
    return $this->externalEntityType->getFieldMapping($field_name)['value'] ?? $field_name;
  }

  /**
   * Get the persistent cache max age.
   *
   * @return int
   */
  protected function getPersistentCacheMaxAge() {
    return $this->externalEntityType->getPersistentCacheMaxAge();
  }

}
