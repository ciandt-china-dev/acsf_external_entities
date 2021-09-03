<?php

namespace Drupal\acsf_external_entities\Plugin\ExternalEntities\AcsfApiClient;

use Drupal\acsf_external_entities\ApiClient\AcsfApiClientBase;

/**
 * Acsf api client based on acsf collection api.
 *
 * @AcsfApiClient(
 *   id = "collection",
 *   label = @Translation("Acsf Collection"),
 *   description = @Translation("Acsf api client based on acsf collection api.")
 * )
 */
class AcsfCollectionApiClient extends AcsfApiClientBase {

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
    return $this->getEndpoint() . '/collections';
  }

  /**
   * {@inheritdoc}
   */
  public function getMultipleDataParameter() {
    return 'collections';
  }

}
