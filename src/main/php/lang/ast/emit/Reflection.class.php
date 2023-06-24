<?php namespace lang\ast\emit;

use lang\{Enum, ClassNotFoundException};

class Reflection extends Type {
  private $reflect;
  private static $UNITENUM;

  /** @codeCoverageIgnore */
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
   * Returns the (fully qualified) default implementations' trait name for
   * interfaces, or `null`.
   *
   * @return ?string
   */
  public function defaultImplementations() {
    if (!$this->reflect->isInterface()) return null;

    $defaults= false === ($p= strrpos($this->reflect->name, '\\'))
      ? "__{$this->reflect->name}_Defaults"
      : substr($this->reflect->name, 0, $p).'\\__'.substr($this->reflect->name, $p + 1).'_Defaults'
    ;
    return trait_exists($defaults, false) ? '\\'.$defaults : null;
  }

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