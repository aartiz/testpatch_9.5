<?php

namespace Drupal\magento\Model;

/**
 * Class SortCriteria.
 *
 * @package \Drupal\magento\Model
 */
class SortCriteria {

  /**
   * Field name.
   *
   * @var string
   */
  public $field;

  /**
   * Sort direction.
   *
   * @var string
   */
  public $direction;

  /**
   * Class constructor.
   *
   * @param array $data
   *   SortCriteria data.
   */
  public function __construct(array $data) {
    $this->field = $data['field'] ?? '';
    $this->direction = $data['direction'] ?? 'ASC';
  }

}
