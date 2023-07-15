<?php namespace lang\ast\emit;

use lang\ast\nodes\{EnumCase, InterfaceDeclaration, Property, Method};

class Declaration extends Type {
  private $type;

  static function __static() { }

  /** @param  lang.ast.nodes.TypeDeclaration $type */
  public function __construct($type) {
    $this->type= $type;
  }

  /** @return string */
  public function name() { return ltrim($this->type->name, '\\'); }

  /** @return iterable */
  public function implementedInterfaces() {
    if ($this->type instanceof InterfaceDeclaration) {
      foreach ($this->type->parents as $interface) {
        yield $interface->literal();
      }
    } else {
      foreach ($this->type->implements as $interface) {
        yield $interface->literal();
      }
    }
  }

  /**
   * Checks whether a given method exists
   *
   * @param  string $named
   * @return bool
   */
  public function providesMethod($named) {
    return isset($this->body["{$named}()"]);
  }

  /**
   * Returns all methods annotated with a given annotation
   *
   * @param  string $annotation
   * @return iterable
   */
  public function methodsAnnotated($annotation) {
    foreach ($this->body as $member) {
      if ($member instanceof Method && $member->annotations && $member->annotations->named($annotation)) {
        yield $member->name => $member->line;
      }
    }
  }

  /**
   * Returns whether a given member is an enum case
   *
   * @param  string $member
   * @return bool
   */
  public function rewriteEnumCase($member) {
    if (!self::$ENUMS && 'enum' === $this->type->kind) {
      return ($this->type->body[$member] ?? null) instanceof EnumCase;
    } else if ('class' === $this->type->kind && $this->type->parent && '\\lang\\Enum' === $this->type->parent->literal()) {
      return ($this->type->body['$'.$member] ?? null) instanceof Property;
    }
    return false;
  }
}