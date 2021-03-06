<?php namespace lang\ast\emit;

use lang\{Enum, ClassNotFoundException};

class Reflection extends Type {
  private $reflect;
  private static $UNITENUM;

  static function __static() {
    self::$UNITENUM= interface_exists(\UnitEnum::class, false);  // Compatibility with XP < 10.8.0
  }

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
  public function rewriteEnumCase($member) {
    if ($this->reflect->isSubclassOf(Enum::class)) {
      return $this->reflect->getStaticPropertyValue($member, null) instanceof Enum;
    } else if (!self::$ENUMS && self::$UNITENUM && $this->reflect->isSubclassOf(\UnitEnum::class)) {
      $value= $this->reflect->getConstant($member) ?: $this->reflect->getStaticPropertyValue($member, null);
      return $value instanceof \UnitEnum;
    }
    return false;
  }
}