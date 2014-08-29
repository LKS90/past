<?php

namespace Drupal\past_db\Entity;

use Drupal\Core\Database\Query\Insert;
use \Drupal\past\Entity\PastEventArgumentInterface;

/**
 * An argument for an event.
 */
class PastEventArgument implements PastEventArgumentInterface {

  public $argument_id;
  protected $original_data;
  public $name;
  public $type;
  public $raw;

  /**
   * Creates a new argument.
   *
   * @param $argument_id
   * @param $name
   * @param $original_data
   * @param array $options
   *   An associative array containing any number of the following properties:
   *     - type
   *     - raw
   */
  public function __construct($argument_id, $name, $original_data, array $options = array()) {
    $this->argument_id = $argument_id;
    $this->name = $name;
    $this->original_data = $original_data;
    if (isset($options['type'])) {
      $this->type = $options['type'];
    }
    if (isset($options['raw'])) {
      $this->raw = $options['raw'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getData() {
    $return = NULL;
    $result = db_select('past_event_data', 'data')
      ->fields('data')
      ->condition('argument_id', $this->argument_id)
      ->execute();

    $is_entity = strpos($this->type, 'entity:') === 0;
    if ($this->type == 'array' || $is_entity) {
      // Array or entity.
      $return = array();
      foreach ($result as $row) {
        $return[$row->name] = $row->serialized ? unserialize($row->value) : $row->value;
      }
      if ($is_entity) {
        $entity_type = substr(strstr($this->type, ':'), 1);
        $return = entity_create($entity_type, $return);
      }
    }
    elseif (!in_array($this->type, array('integer', 'string', 'float', 'double', 'boolean'))) {
      // Object other than entity.
      $return = new \stdClass();
      foreach ($result as $row) {
        $return->{$row->name} = $row->serialized ? unserialize($row->value) : $row->value;
      }
    }
    else {
      // Scalar.
      if ($row = $result->fetchAssoc()) {
        settype($row['value'], $this->type);
        $return = $row['value'];
      }
    }
    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function getKey() {
    return $this->name;
  }

  /**
   * {@inheritdoc}
   */
  public function getRaw() {
    return $this->raw;
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return $this->type;
  }

  /**
   * {@inheritdoc}
   */
  public function setRaw($data, $json_encode = TRUE) {

  }

  /**
   * {@inheritdoc}
   */
  public function getOriginalData() {
    return $this->original_data;
  }

  /**
   * {@inheritdoc}
   */
  public function ensureType() {
    if (isset($this->original_data) && !isset($this->type)) {
      if (is_object($this->original_data)) {
        $this->type = get_class($this->original_data);
      }
      else {
        $this->type = gettype($this->original_data);
      }
    }
  }

  /**
   * Defines the argument entity label.
   *
   * @return string
   *   Entity label.
   */
  public function defaultLabel() {
    return $this->getKey();
  }
}
