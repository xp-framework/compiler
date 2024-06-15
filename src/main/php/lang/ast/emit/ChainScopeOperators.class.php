<?php namespace lang\ast\emit;

use lang\ast\Node;
use lang\ast\nodes\Literal;

/**
 * Allows chaining of scope operators and rewrites `[expr]::class` to `get_class($object)`
 * except if expression references a type - e.g. `self` or `ClassName`.
 * 
 * @see  https://wiki.php.net/rfc/class_name_literal_on_object
 * @see  https://wiki.php.net/rfc/variable_syntax_tweaks#constant_dereferencability
 * @test lang.ast.unittest.emit.ChainScopeOperatorsTest
 */
trait ChainScopeOperators {
  use RewriteDynamicClassConstants { emitScope as rewriteDynamicClassConstants; }

  protected function emitScope($result, $scope) {
    if (!($scope->type instanceof Node)) return $this->rewriteDynamicClassConstants($result, $scope);

    if ($scope->member instanceof Literal && 'class' === $scope->member->expression) {
      $result->out->write('\\get_class(');
      $this->emitOne($result, $scope->type);
      $result->out->write(')');
    } else {
      $t= $result->temp();
      $result->out->write('(null==='.$t.'=');
      $this->emitOne($result, $scope->type);
      $result->out->write(")?null:{$t}::");
      $this->emitOne($result, $scope->member);
    }
  }
}