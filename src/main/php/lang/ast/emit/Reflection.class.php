<?php namespace lang\ast\emit;

use Override, UnitEnum, ReflectionClass, ReflectionException;
use lang\Reflection as Reflect;
use lang\ast\Error;
use lang\{Enum, ClassNotFoundException};

class Reflection extends Type {
  private $reflect;
  private static $UNITENUM;

  /** @codeCoverageIgnore */
  static function __static() {
    self::$UNITENUM= interface_exists(UnitEnum::class, false);  // Compatibility with XP < 10.8.0
  }

  /** @param string $type */
  public function __construct($type) {
    try {
      $this->reflect= new ReflectionClass($type);
    } catch (ReflectionException $e) {
      throw new ClassNotFoundException($type);
    }
  }

  /** @return string */
  public function name() { return $this->reflect->name; }

  /**
   * Checks whether a given method exists
   *
   * @param  string $named
   * @param  ?int $select
   * @return bool
   */
  public function providesMethod($named, $select= null) {
    if ($this->reflect->hasMethod($named)) {
      return null === $select || (bool)($this->reflect->getMethod($named)->getModifiers() & $select);
    }
    return false;
  }

  /**
   * Checks `#[Override]`
   *
   * @param  lang.ast.emit.Type $type
   * @return void
   * @throws lang.ast.Error
   */
  public function checkOverrides($type) {
    $meta= Reflect::meta();
    foreach ($this->reflect->getMethods() as $method) {
      if (isset($meta->methodAnnotations($method)[Override::class])) {
        $type->checkOverride($method->getName(), $method->getStartLine());
      }
    }
  }

  /**
   * Checks `#[Override]` for a given method
   *
   * @param  string $method
   * @param  int $line
   * @return void
   * @throws lang.ast.Error
   */
  public function checkOverride($method, $line) {

    // Ignore traits, check parents and interfaces for all other types
    if ($this->reflect->isTrait()) {
      return;
    } else if ($parent= $this->reflect->getParentClass()) {
      if ($parent->hasMethod($method)) {
        if (!$this->reflect->getMethod($named)->isPrivate()) return;
      }
    } else {
      foreach ($this->type->getInterfaces() as $interface) {
        if ($interface->hasMethod($method)) return;
      }
    }

    throw new Error(
      sprintf(
        '%s::%s() has #[\\Override] attribute, but no matching parent method exists',
        $this->reflect->isAnonymous() ? 'class@anonymous' : $this->reflect->getName(),
        $method
      ),
      $this->reflect->getFileName(),
      $line
    );
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
    } else if (!self::$ENUMS && self::$UNITENUM && $this->reflect->isSubclassOf(UnitEnum::class)) {
      $value= $this->reflect->getConstant($member) ?: $this->reflect->getStaticPropertyValue($member, null);
      return $value instanceof UnitEnum;
    }
    return false;
  }
}