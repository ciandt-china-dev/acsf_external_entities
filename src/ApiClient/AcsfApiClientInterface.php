<?php

namespace Drupal\acsf_external_entities\ApiClient;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\external_entities\ExternalEntityInterface;

/**
 * Interface AcsfApiClientInterface.
 *
 * @package Drupal\acsf_external_entities
 */
interface AcsfApiClientInterface extends PluginInspectionInterface, ContainerFactoryPluginInterface {

  const CACHE_PREFIX = 'acsf_api_client';

  const REQUEST_TYPE_ONE = 'request_one';

  const REQUEST_TYPE_MULTIPLE = 'request_multiple';

  /**
   * Get the cache.
   *
   * @return \Drupal\Core\Cache\CacheBackendInterface
   */
  public function getCache();

  /**
   * Get cache key.
   *
   * @param string $type
   *   The cache type.
   *
   * @return string
   */
  public function getCacheKey(string $type = '');

  /**
   * Get cache tags.
   *
   * @return array
   */
  public function getCacheTags();

  /**
   * Get cache tags to invalidate.
   *
   * @return array
   */
  public function getCacheTagsToInvalidate();

  /**
   * Set the maximum age for this api client persistent cache.
   *
   * @return \self
   */
  public function setPersistentCacheMaxAge(int $max_age);

  /**
   * Gets the maximum age for this api client persistent cache.
   *
   * @return int
   *   The maximum age in seconds. -1 means the api client are cached permanently,
   *   while 0 means entity caching for this external entity type is disabled.
   */
  public function getPersistentCacheMaxAge();

  /**
   * Set the id key.
   *
   * @param string $id_key
   *   The id key.
   *
   * @return \self
   */
  public function setIdKey(string $id_key);

  /**
   * Get the id key.
   *
   * @return string
   */
  public function getIdKey();

  /**
   * Get the origin id key.
   *
   * @return string
   */
  public function getOriginIdKey();

  /**
   * Return the name of the acsf api client.
   *
   * @return string
   *   The name of the acsf api client.
   */
  public function getName();

  /**
   * Return the version of the acsf api client.
   *
   * @return string
   *   The version of the acsf api client, e.g. v1, v2.
   */
  public function getEndpointVersion();

  /**
   * Get the http client.
   *
   * @return \GuzzleHttp\ClientInterface
   *   The http client.
   */
  public function getHttpClient();

  /**
   * Get the response decoder factory.
   *
   * @return \Drupal\external_entities\ResponseDecoder\ResponseDecoderFactoryInterface
   *   The response decoder factory.
   */
  public function getResponseDecoderFactory();

  /**
   * Gets the HTTP headers to add to a request.
   *
   * @return array
   *   Associative array of headers to add to the request.
   */
  public function getHttpHeaders();

  /**
   * Gets the HTTP queries to add to a request.
   *
   * @param int|null $limit
   *   The limit.
   * @param int|null $page
   *   The page
   *
   * @return array
   *   Associative array of queries to add to the request.
   */
  public function getHttpQueries($limit = NULL, $page = NULL);

  /**
   * Set api username.
   *
   * @param string $username
   *
   * @return \self
   */
  public function setUsername(string $username);

  /**
   * Get api username.
   *
   * @return string
   *   The username.
   */
  public function getUsername();

  /**
   * Set api password.
   *
   * @param string $password
   *
   * @return \self
   */
  public function setPassword(string $password);

  /**
   * Get api password.
   *
   * @return string
   *   The password.
   */
  public function getPassword();

  /**
   * Set api endpoint.
   *
   * @param string $endpoint
   *
   * @return \self
   */
  public function setEndpoint(string $endpoint);

  /**
   * Get api endpoint.
   *
   * @return string
   *   The endpoint.
   */
  public function getEndpoint();

  /**
   * Get uri string.
   *
   * @return string
   *   The request uri.
   */
  public function getUri();

  /**
   * The parameter of request multiple data stored.
   *
   * @return string
   */
  public function getMultipleDataParameter();

  /**
   * Loads one data.
   *
   * @param mixed $id
   *  The ID of the data to load.
   *
   * @return array|null
   */
  public function request($id);

  /**
   * Get multiple data.
   *
   * @param int $limit
   *   The limit.
   * @param int $page
   *   The page.
   *
   * @return array
   */
  public function requestMultiple(int $limit = 100, int $page = 1);

  /**
   * Get all data.
   *
   * @return array
   */
  public function requestAll();

  /**
   * Add a item by api.
   */
  public function add(ExternalEntityInterface $entity);

  /**
   * Update a item by api.
   */
  public function update(ExternalEntityInterface $entity);

  /**
   * Delete a item by api.
   */
  public function delete(ExternalEntityInterface $entity);

}
