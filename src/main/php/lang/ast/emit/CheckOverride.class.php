<?php namespace lang\ast\emit;

use Override;
use lang\ast\Error;

/**
 * Verifies `#[Override]`.
 *
 * @see   https://wiki.php.net/rfc/marking_overriden_methods
 * @test  lang.ast.unittest.emit.MethodOverridingTest
 */
trait CheckOverride {

  protected function emitMethod($result, $method) {
    if ($method->annotations && $method->annotations->named(Override::class)) {

      // Check parent class
      if (($parent= $result->codegen->lookup('parent')) && $parent->providesMethod($method->name)) goto emit;

      // Check all implemented interfaces
      foreach ($result->codegen->lookup('self')->implementedInterfaces() as $interface) {
        if ($result->codegen->lookup($interface)->providesMethod($method->name)) goto emit;
      }

      throw new Error(
        sprintf(
          '%s::%s() has #[\\Override] attribute, but no matching parent method exists',
          substr($result->codegen->scope[0]->type->name, 1),
          $method->name
        ),
        $result->codegen->source,
        $method->line
      );
    }

    emit: parent::emitMethod($result, $method);
  }
}