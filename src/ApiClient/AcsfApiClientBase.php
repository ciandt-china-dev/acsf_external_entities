<?php

namespace Drupal\acsf_external_entities\ApiClient;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\external_entities\ExternalEntityInterface;
use Drupal\external_entities\ResponseDecoder\ResponseDecoderFactoryInterface;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class AcsfApiClientBase.
 *
 * @package Drupal\acsf_external_entities\ApiClient
 */
abstract class AcsfApiClientBase extends PluginBase implements AcsfApiClientInterface {

  /**
   * The id key.
   *
   * @var string
   */
  protected $idKey = '';

  /**
   * The api username.
   *
   * @var string
   */
  protected $username = '';

  /**
   * The api password.
   *
   * @var string
   */
  protected $password = '';

  /**
   * The api endpoint.
   *
   * @var string
   */
  protected $endpoint = '/';

  /**
   * The persistent cache max age.
   *
   * @var int
   */
  protected $persistentCacheMaxAge = Cache::PERMANENT;

  /**
   * The HTTP client to fetch the files with.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The response decoder factory.
   *
   * @var \Drupal\external_entities\ResponseDecoder\ResponseDecoderFactoryInterface
   */
  protected $responseDecoderFactory;

  /**
   * The cache.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a ExternalEntityStorageClientBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The http client.
   * @param \Drupal\external_entities\ResponseDecoder\ResponseDecoderFactoryInterface $response_decoder_factory
   *   The response decoder factory.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, TranslationInterface $string_translation, ClientInterface $http_client, ResponseDecoderFactoryInterface $response_decoder_factory, CacheBackendInterface $cache, ModuleHandlerInterface $module_handler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->setStringTranslation($string_translation);
    $this->httpClient = $http_client;
    $this->responseDecoderFactory = $response_decoder_factory;
    $this->cache = $cache;
    $this->moduleHandler = $module_handler;
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
      $container->get('http_client'),
      $container->get('external_entities.response_decoder_factory'),
      $container->get('cache.default'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getCache() {
    if (!$this->cache) {
      $this->cache = \Drupal::cache();
    }
    return $this->cache;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheKey(string $type = '', $parameters = []) {
    $array = array_merge(
      ['acsf_api_client', $this->getPluginId(), $type],
      $parameters
    );
    return implode('.', $array);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return Cache::mergeTags([
      self::CACHE_PREFIX,
      self::CACHE_PREFIX . ':' . $this->getPluginId(),
    ], $this->getCacheTagsToInvalidate());
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTagsToInvalidate() {
    return [
      // Cache Tag: request_one.
      self::CACHE_PREFIX . ':' . $this->getPluginId() . ':' . self::REQUEST_TYPE_ONE,
      // Cache Tag: request_multiple.
      self::CACHE_PREFIX . ':' . $this->getPluginId() . ':' . self::REQUEST_TYPE_MULTIPLE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function setPersistentCacheMaxAge(int $max_age = Cache::PERMANENT) {
    $this->persistentCacheMaxAge = $max_age;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPersistentCacheMaxAge() {
    return $this->persistentCacheMaxAge;
  }

  /**
   * {@inheritdoc}
   */
  public function setIdKey(string $id_key) {
    $this->idKey = $id_key;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getIdKey() {
    return $this->idKey;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->pluginDefinition['name'];
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    $plugin_definition = $this->getPluginDefinition();
    return $plugin_definition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    $plugin_definition = $this->getPluginDefinition();
    return isset($plugin_definition['description']) ? $plugin_definition['description'] : '';
  }

  /**
   * {@inheritdoc}
   */
  public function getHttpClient() {
    if (!$this->httpClient) {
      $this->httpClient = \Drupal::httpClient();
    }
    return $this->httpClient;
  }

  /**
   * {@inheritdoc}
   */
  public function getResponseDecoderFactory() {
    if (!$this->responseDecoderFactory) {
      $this->responseDecoderFactory = \Drupal::service('external_entities.response_decoder_factory');
    }
    return $this->responseDecoderFactory;
  }

  /**
   * {@inheritdoc}
   */
  public function getHttpHeaders() {
    $headers = [
      'Authorization' => 'Basic ' . base64_encode($this->getUsername() . ':' . $this->getPassword()),
    ];
    return $headers;
  }

  /**
   * {@inheritdoc}
   */
  public function getHttpQueries($limit = NULL, $page = NULL) {
    $queries = [];

    if (!is_null($limit)) {
      $queries['limit'] = $limit;
    }

    if (!is_null($page)) {
      $queries['page'] = $page;
    }

    return $queries;
  }

  /**
   * {@inheritdoc}
   */
  public function setUsername(string $username) {
    $this->username = $username;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getUsername() {
    return $this->username;
  }

  /**
   * {@inheritdoc}
   */
  public function setPassword(string $password) {
    $this->password = $password;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPassword() {
    return $this->password;
  }

  /**
   * {@inheritdoc}
   */
  public function setEndpoint(string $endpoint) {
    $this->endpoint = $endpoint . '/' . $this->getEndpointVersion();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getEndpoint() {
    return $this->endpoint;
  }

  /**
   * @param string $type
   *   The hook type.
   * @param array|null $data
   *   The data.
   * @param mixed $context1
   *   The context1.
   * @param mixed $context2
   *   The context2.
   */
  protected function invokeHooks(string $type, array &$data = NULL, &$context1, &$context2) {
    $this->moduleHandler->alter([
      self::CACHE_PREFIX . '_' . $type . '_data',
      self::CACHE_PREFIX . '_' . $this->getPluginId() . '_' . $type . '_data',
    ], $data, $cache_context, $this);
  }

  /**
   * {@inheritdoc}
   */
  public function request($id) {
    $cache_key = $this->getCacheKey(self::REQUEST_TYPE_ONE, [$id]);

    $data = $this->getCache()->get($cache_key) ?: NULL;
    $data = $data ? $data->data : NULL;

    if (!$data) {
      try {
        $response = $this->getHttpClient()->request(
          'GET',
          $this->getUri() . '/' . $id,
          [
            'headers' => $this->getHttpHeaders(),
          ]
        );

        $body = $response->getBody() . '';

        $data = $this->getResponseDecoderFactory()->getDecoder('json')->decode($body);
      }
      catch (\Exception $e) {
        $data = NULL;
      }

      // Cache metadata.
      $cache_context = [
        'id' => $id,
        'tags' => Cache::mergeTags($this->getCacheTags(), [
          self::CACHE_PREFIX . ':' . self::REQUEST_TYPE_ONE,
          self::CACHE_PREFIX . ':' . $this->getPluginId() . ':' . self::REQUEST_TYPE_ONE,
          self::CACHE_PREFIX . ':' . $this->getPluginId() . ':' . self::REQUEST_TYPE_ONE . ':' . $id,
        ]),
        'cacheable' => TRUE,
      ];

      // Hooks:
      // acsf_api_client_request_one_data,
      // acsf_api_client_PLUGIN_ID_request_one_data.
      $this->invokeHooks(self::REQUEST_TYPE_ONE, $data, $cache_context, $this);

      // Cache for one data if cacheable.
      if (!is_null($data) && $cache_context['cacheable']) {
        $this->getCache()->set($cache_key, $data, $this->getPersistentCacheMaxAge(), $cache_context['tags']);
      }
    }

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function requestMultiple($limit = 100, $page = 1) {
    $cache_key = $this->getCacheKey(self::REQUEST_TYPE_MULTIPLE, [$limit, $page]);

    $data = $this->getCache()->get($cache_key) ?: [];
    $data = $data ? $data->data : [];

    if (!$data) {
      try {
        $response = $this->getHttpClient()->request(
          'GET',
          $this->getUri(),
          [
            'headers' => $this->getHttpHeaders(),
            'query' => $this->getHttpQueries($limit, $page),
          ]
        );

        $body = $response->getBody() . '';

        $results = $this->getResponseDecoderFactory()->getDecoder('json')->decode($body);

        // Use origin_id_key's value for array key.
        $data = array_column($results[$this->getMultipleDataParameter()] ?? [], NULL, $this->getOriginIdKey());
      }
      catch (\Exception $e) {
        $data = [];
      }

      // Cache metadata.
      $cache_context = [
        'limit' => $limit,
        'page' => $page,
        'tags' => Cache::mergeTags($this->getCacheTags(), [
          self::CACHE_PREFIX . ':' . self::REQUEST_TYPE_MULTIPLE,
          self::CACHE_PREFIX . ':' . $this->getPluginId() . ':' . self::REQUEST_TYPE_MULTIPLE,
        ]),
        'cacheable' => TRUE,
      ];

      // Hooks:
      // acsf_api_client_request_multiple_data,
      // acsf_api_client_PLUGIN_ID_request_multiple_data.
      $this->invokeHooks(self::REQUEST_TYPE_MULTIPLE, $data, $cache_context, $this);

      // Cache for multiple data if cacheable.
      if (!is_null($data) && $cache_context['cacheable']) {
        $this->getCache()->set($cache_key, $data, $this->getPersistentCacheMaxAge(), $cache_context['tags']);
      }
    }

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function requestAll() {
    $data = [];
    $page = 1;
    while ($page === 1 || count($data) === 100 * $page) {
      $data += $this->requestMultiple(100, $page);
      $page += 1;
    }
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function add(ExternalEntityInterface $entity) {
    return 0;
  }

  /**
   * {@inheritdoc}
   */
  public function update(ExternalEntityInterface $entity) {
    return 0;
  }

  /**
   * {@inheritdoc}
   */
  public function delete(ExternalEntityInterface $entity) {
    return 0;
  }

}
