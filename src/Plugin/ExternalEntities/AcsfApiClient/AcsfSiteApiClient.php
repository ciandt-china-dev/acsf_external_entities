<?php

namespace Drupal\acsf_external_entities\Plugin\ExternalEntities\AcsfApiClient;

use Drupal\acsf_external_entities\ApiClient\AcsfApiClientBase;
use Drupal\Core\Cache\Cache;

/**
 * Acsf api client based on acsf site api.
 *
 * @AcsfApiClient(
 *   id = "site",
 *   label = @Translation("Acsf Site"),
 *   description = @Translation("Acsf api client based on acsf site api.")
 * )
 */
class AcsfSiteApiClient extends AcsfApiClientBase {

  /**
   * {@inheritdoc}
   */
  public function getOriginIdKey() {
    return 'id';
  }

  /**
   * {@inheritdoc}
   */
  public function getEndpointVersion() {
    return 'v1';
  }

  /**
   * {@inheritdoc}
   */
  public function getUri() {
    return $this->getEndpoint() . '/sites';
  }

  /**
   * {@inheritdoc}
   */
  public function getMultipleDataParameter() {
    return 'sites';
  }

  /**
   * {@inheritdoc}
   */
  public function requestMultiple($limit = 100, $page = 1) {
    $overwritten_cache_key = $this->getCacheKey(self::REQUEST_TYPE_MULTIPLE, [$limit, $page, 'overwritten']);

    $data = $this->getCache()->get($overwritten_cache_key) ?: [];
    $data = $data ? $data->data : [];

    if (!$data) {
      $data = parent::requestMultiple($limit, $page);

      // Because of the site list api cannot get site more details, so by request function again.
      foreach ($data as $id => $site) {
        $data[$id] = $this->request($id);
      }

      // Cache metadata.
      $cache_context = [
        'limit' => $limit,
        'page' => $page,
        'overwritten' => TRUE,
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
        $this->getCache()->set($overwritten_cache_key, $data, $this->getPersistentCacheMaxAge(), $cache_context['tags']);
      }
    }

    return $data;
  }

}
