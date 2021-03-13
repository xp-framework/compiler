<?php namespace lang\ast\types;

use lang\ast\nodes\{EnumCase, Property};

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
   * Returns whether a given member is an enum case
   *
   * @param  string $member
   * @return bool
   */
  public function rewriteEnumCase($member) {
    if (!self::$ENUMS && 'enum' === $this->type->kind) {
      return ($this->type->body[$member] ?? null) instanceof EnumCase;
    } else if ('class' === $this->type->kind && '\\lang\\Enum' === $this->type->parent) {
      return ($this->type->body['$'.$member] ?? null) instanceof Property;
    }
    return false;
  }
}