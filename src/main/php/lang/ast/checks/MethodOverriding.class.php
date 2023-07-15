<?php namespace lang\ast\checks;

use Override;

/** @see https://wiki.php.net/rfc/marking_overriden_methods */
class MethodOverriding extends Check {

  /** @return string */
  public function nodeKind() { return 'method'; }

  /**
   * Checks node and returns errors
   *
   * @param  lang.ast.CodeGen $codegen
   * @param  lang.ast.Node $node
   * @return iterable
   */
  public function check($codegen, $method) {
    if ($method->annotations && $method->annotations->named(Override::class)) {
      if (!($parent= $codegen->lookup('parent')) || !$parent->providesMethod($method->name)) {
        yield sprintf(
          '%s:%s() has #[\\Override] attribute, but no matching parent method exists',
          substr($codegen->scope[0]->type->name, 1),
          $method->name
        );
      }
    }
  }
}