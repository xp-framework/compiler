<?php namespace lang\ast\emit;

use lang\ast\nodes\{EnumCase, Property, Method};

class Declaration extends Type {
  private $type, $result;

  static function __static() { }

  /**
   * @param  lang.ast.nodes.TypeDeclaration $type
   * @param  lang.ast.Result $result
   */
  public function __construct($type, $result) {
    $this->type= $type;
    $this->result= $result;
  }

  /** @return string */
  public function name() { return ltrim($this->type->name, '\\'); }

  /**
   * Returns whether this is an interface with default implementations
   *
   * @return bool
   */
  public function defaultImplementations() {
    if ('interface' === $this->type->kind) {
      foreach ($this->type->body as $member) {
        if ($member instanceof Method && null !== $member->body) return true;
      }
    }
    return false;
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