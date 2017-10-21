<?php namespace lang\ast;

class MapType extends Type {
  public $key, $value;

  /**
   * Creates a new type
   *
   * @param  self $key
   * @param  self $value
   */
  public function __construct($key, $value) {
    $this->key= $key;
    $this->value= $value;
  }

  /** @return string */
  public function literal() { return 'array'; }

  /** @return string */
  public function name() { return '[:'.$this->value->name().']'; }
}