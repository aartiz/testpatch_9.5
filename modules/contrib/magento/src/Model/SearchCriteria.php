<?php

namespace Drupal\magento\Model;

/**
 * Class SearchCriteria.
 *
 * @package \Drupal\magento\Model
 */
class SearchCriteria {

  /**
   * Condition.
   *
   * @var string
   */
  public $condition;

  /**
   * Field name.
   *
   * @var string
   */
  public $field;

  /**
   * Value.
   *
   * @var string
   */
  public $value;

  /**
   * Class constructor.
   *
   * @param array $data
   *   SearchCriteria data.
   */
  public function __construct(array $data) {
    $this->condition = $data['condition'] ?? 'eq';
    $this->field = $data['field'] ?? '';
    $this->value = $data['value'] ?? '';
  }

}
