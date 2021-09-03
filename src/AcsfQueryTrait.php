<?php

namespace Drupal\acsf_external_entities;

/**
 * Trait AcsfQueryTrait.
 *
 * @package Drupal\acsf_external_entities
 */
trait AcsfQueryTrait {

  /**
   * Query operate.
   *
   * @param array $data
   *  The data.
   * @param string $field
   *  The field.
   * @param $value
   *  The value.
   * @param string|null $operator
   *  The operator.
   *
   * @return bool
   */
  protected function queryOperate(array $data, string $field, $value, $operator = NULL) {
    if (!isset($data[$field])) {
      return FALSE;
    }

    if (empty($operator)) {
      $operator = '=';
    }

    switch ($operator) {
      case '=':
        return $data[$field] == $value;

      case '<>':
        return $data[$field] != $value;

      case '>':
        return $data[$field] > $value;

      case '>=':
        return $data[$field] >= $value;

      case '<':
        return $data[$field] < $value;

      case '<=':
        return $data[$field] <= $value;

      case 'STARTS_WITH':
        return substr($data[$field], strlen($value)) === $value;

      case 'CONTAINS':
        return stripos($data[$field], $value) !== FALSE;

      case 'IN':
        return in_array($data[$field], $value);

      case 'NOT IN':
        return !in_array($data[$field], $value);

      case 'BETWEEN':
        return $value[0] <= $data[$field] && $data[$field] <= $value[1];
    }

    return FALSE;
  }

  /**
   * Query sort.
   *
   * @param array $data1
   *  The data.
   * @param array $data2
   *  The data.
   * @param string $field
   *  The field.
   * @param string $direction
   *  The direction.
   *
   * @return int
   */
  protected function querySort(array $data1, array $data2, string $field, string $direction = 'ASC') {
    if (!isset($data1[$field]) || !isset($data2[$field])) {
      return 0;
    }

    switch ($direction) {
      case 'ASC':
      case 'asc':
        return $data1[$field] <=> $data2[$field];

      case 'DESC':
      case 'desc':
        return $data2[$field] <=> $data1[$field];
    }

    return 0;
  }

}
