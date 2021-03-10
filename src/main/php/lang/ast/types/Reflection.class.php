<?php namespace lang\ast\types;

use lang\{Enum, ClassNotFoundException};

class Reflection implements Type {
  private $reflect;

  /** @param string $type */
  public function __construct($type) {
    try {
      $this->reflect= new \ReflectionClass($type);
    } catch (\ReflectionException $e) {
      throw new ClassNotFoundException($type);
    }
  }

  /** @return string */
  public function name() { return $this->reflect->name; }

  /**
   * Returns whether a given member is an enum case
   *
   * @param  string $member
   * @return bool
   */
  public function isEnumCase($member) {
    if ($this->reflect->isSubclassOf(Enum::class)) {
      return $this->reflect->getStaticPropertyValue($member, null) instanceof Enum;
    } else if ($this->reflect->isSubclassOf(\UnitEnum::class)) {
      $value= $this->reflect->getConstant($member) ?: $this->reflect->getStaticPropertyValue($member, null);
      return $value instanceof \UnitEnum;
    }
    return false;
  }
}