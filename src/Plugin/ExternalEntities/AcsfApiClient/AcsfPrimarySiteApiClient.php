<?php

namespace Drupal\acsf_external_entities\Plugin\ExternalEntities\AcsfApiClient;

use Drupal\acsf_external_entities\ApiClient\AcsfApiClientBase;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\external_entities\ResponseDecoder\ResponseDecoderFactoryInterface;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Acsf api client based on acsf site api.
 *
 * @AcsfApiClient(
 *   id = "primary_site",
 *   label = @Translation("Acsf Primary Site"),
 *   description = @Translation("Acsf api client based on acsf collection site api, filtered by is_primary.")
 * )
 */
class AcsfPrimarySiteApiClient extends AcsfApiClientBase {

  /**
   * The acsf collection api client.
   *
   * @var \Drupal\acsf_external_entities\ApiClient\AcsfApiClientInterface
   */
  protected $acsfCollectionApiClient;

  /**
   * The acsf site api client.
   *
   * @var \Drupal\acsf_external_entities\ApiClient\AcsfApiClientInterface
   */
  protected $acsfSiteApiClient;

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
   * @param \Drupal\Component\Plugin\PluginManagerInterface $acsf_api_client_manager
   *   The acsf api client manager.
   */
  public function __construct(array $configuration, string $plugin_id, $plugin_definition, TranslationInterface $string_translation, ClientInterface $http_client, ResponseDecoderFactoryInterface $response_decoder_factory, CacheBackendInterface $cache, ModuleHandlerInterface $module_handler, PluginManagerInterface $acsf_api_client_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $string_translation, $http_client, $response_decoder_factory, $cache, $module_handler);
    $this->acsfCollectionApiClient = $acsf_api_client_manager->createInstance('collection');
    $this->acsfSiteApiClient = $acsf_api_client_manager->createInstance('site');
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
      $container->get('module_handler'),
      $container->get('plugin.manager.acsf_external_entities.acsf_api_client')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getOriginIdKey() {
    return $this->acsfSiteApiClient->getOriginIdKey();
  }

  /**
   * {@inheritdoc}
   */
  public function getEndpointVersion() {
    return $this->acsfSiteApiClient->getEndpointVersion();
  }

  /**
   * {@inheritdoc}
   */
  public function getUri() {
    return $this->acsfSiteApiClient->getUri();
  }

  /**
   * {@inheritdoc}
   */
  public function getMultipleDataParameter() {
    return $this->acsfSiteApiClient->getMultipleDataParameter();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $cache_tags = Cache::mergeTags(parent::getCacheTags(), $this->acsfCollectionApiClient->getCacheTags());
    $cache_tags = Cache::mergeTags($cache_tags, $this->acsfSiteApiClient->getCacheTags());
    return $cache_tags;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTagsToInvalidate() {
    $cache_tags = Cache::mergeTags(parent::getCacheTagsToInvalidate(), $this->acsfCollectionApiClient->getCacheTagsToInvalidate());
    $cache_tags = Cache::mergeTags($cache_tags, $this->acsfSiteApiClient->getCacheTagsToInvalidate());
    return $cache_tags;
  }

  /**
   * {@inheritdoc}
   */
  public function setPersistentCacheMaxAge(int $max_age = Cache::PERMANENT) {
    parent::setPersistentCacheMaxAge($max_age);
    $this->acsfSiteApiClient->setPersistentCacheMaxAge($max_age);
    $this->acsfCollectionApiClient->setPersistentCacheMaxAge($max_age);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setIdKey(string $id_key) {
    parent::setIdKey($id_key);
    $this->acsfSiteApiClient->setIdKey($id_key);
    $this->acsfCollectionApiClient->setIdKey($this->acsfCollectionApiClient->getOriginIdKey());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setUsername(string $username) {
    parent::setUsername($username);
    $this->acsfSiteApiClient->setUsername($username);
    $this->acsfCollectionApiClient->setUsername($username);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setPassword(string $password) {
    parent::setPassword($password);
    $this->acsfSiteApiClient->setPassword($password);
    $this->acsfCollectionApiClient->setPassword($password);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setEndpoint(string $endpoint) {
    parent::setEndpoint($endpoint);
    $this->acsfSiteApiClient->setEndpoint($endpoint);
    $this->acsfCollectionApiClient->setEndpoint($endpoint);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function request($id) {
    $cache_key = $this->getCacheKey(self::REQUEST_TYPE_ONE, [$id]);

    $data = $this->getCache()->get($cache_key) ?: NULL;
    $data = $data ? $data->data : NULL;

    if (!$data) {
      $data = $this->acsfSiteApiClient->request($id);

      if ($data) {
        $collection = $this->acsfCollectionApiClient->request($data['collection_id']);
        $data['collection_name'] = $collection['name'] ?? $data['site'];
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
      // Get all sites.
      $sites = $this->acsfSiteApiClient->requestAll();

      // Filter primary site.
      $sites = array_filter($sites, function ($site) {
        return $site['is_primary'] ?? FALSE;
      });

      // Get site details to override site list data.
      $data = array_map(function ($site) {
        return $this->request($site['id']);
      }, $sites);

      // Paging.
      $data = array_slice($data, ($page - 1) * $limit, $limit, TRUE);

      // Cache context.
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
      if ($cache_context['cacheable']) {
        $this->getCache()->set($cache_key, $data, $this->getPersistentCacheMaxAge(), $cache_context['tags']);
      }
    }

    return $data;
  }


}
