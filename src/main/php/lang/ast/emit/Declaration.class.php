<?php namespace lang\ast\emit;

use lang\ast\Error;
use lang\ast\nodes\{EnumCase, InterfaceDeclaration, TraitDeclaration, Property, Method};

class Declaration extends Type {
  private $type, $codegen;

  static function __static() { }

  /** @param  lang.ast.nodes.TypeDeclaration $type */
  public function __construct($type, $codegen) {
    $this->type= $type;
    $this->codegen= $codegen;
  }

  /** @return string */
  public function name() { return ltrim($this->type->name, '\\'); }

  /**
   * Checks `#[Override]`
   *
   * @param  lang.ast.emit.Type $type
   * @return void
   * @throws lang.ast.Error
   */
  public function checkOverrides($type) {
    foreach ($this->type->body as $member) {
      if ($member instanceof Method && $member->annotations && $member->annotations->named($annotation)) {
        $type->checkOverride($member->name, $member->line);
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
    if ($this->type instanceof TraitDeclaration) {

      // Do not check traits, this is done when including them into the type
      return;
    } else if ($this->type instanceof InterfaceDeclaration) {

      // Check parent interfaces
      foreach ($this->type->parents as $interface) {
        if ($this->codegen->lookup($interface->literal())->providesMethod($method)) return;
      }
    } else {

      // Check parent, then check all implemented interfaces
      if ($this->type->parent && $this->codegen->lookup('parent')->providesMethod($method)) return;
      foreach ($this->type->implements as $interface) {
        if ($this->codegen->lookup($interface->literal())->providesMethod($method)) return;
      }
    }

    throw new Error(
      sprintf(
        '%s::%s() has #[\\Override] attribute, but no matching parent method exists',
        isset($this->type->name) ? substr($this->type->name->literal(), 1) : 'class@anonymous',
        $method
      ),
      $this->codegen->source,
      $line
    );
  }

  /**
   * Checks whether a given method exists
   *
   * @param  string $named
   * @return bool
   */
  public function providesMethod($named) {
    return isset($this->type->body["{$named}()"]);
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