<?php

namespace Drupal\acsf_external_entities\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an acsf api client annotation object.
 *
 * @see \Drupal\acsf_external_entities\ApiClient\AcsfApiClientManager
 * @see plugin_api
 *
 * @Annotation
 */
class AcsfApiClient extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The name of the api client.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $name;

}
