<?php

namespace Drupal\acsf_external_entities\Plugin\ExternalEntities\AcsfApiClient;

use Drupal\acsf_external_entities\ApiClient\AcsfApiClientBase;

/**
 * Acsf api client based on acsf group api.
 *
 * @AcsfApiClient(
 *   id = "group",
 *   label = @Translation("Acsf Group"),
 *   description = @Translation("Acsf api client based on acsf group api.")
 * )
 */
class AcsfGroupApiClient extends AcsfApiClientBase {

  /**
   * {@inheritdoc}
   */
  public function getOriginIdKey() {
    return 'group_id';
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
    return $this->getEndpoint() . '/groups';
  }

  /**
   * {@inheritdoc}
   */
  public function getMultipleDataParameter() {
    return 'groups';
  }

}
