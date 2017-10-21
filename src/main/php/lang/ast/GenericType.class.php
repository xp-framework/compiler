<?php namespace lang\ast;

use util\Objects;

class GenericType implements \lang\Value {
  public $base, $components;

  /**
   * Creates a new type
   *
   * @param  string $type
   * @param  self[] $components
   */
  public function __construct($base, $components= []) {
    $this->base= $base;
    $this->components= $components;
  }

  /** @return string */
  public function name() {
    return $this->base.($this->components
      ? '<'.implode(', ', array_map([Objects::class, 'stringOf'], $this->components)).'>'
      : ''
    );
  }

  /** @return string */
  public function toString() {
    return $this->base.($this->components
      ? '<'.implode(', ', array_map([Objects::class, 'stringOf'], $this->components)).'>'
      : ''
    );
  }

  /** @return string */
  public function hashCode() {
    return Objects::hashOf([$this->base, $this->components]);
  }

  /**
   * Compare
   *
   * @param  var $value
   * @return int
   */
  public function compareTo($value) {
    return $value instanceof self
      ? Objects::compare([$this->base, $this->components], [$value->base, $value->components])
      : 1
    ;
  }
}