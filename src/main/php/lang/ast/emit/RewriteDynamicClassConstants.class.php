<?php namespace lang\ast\emit;

use lang\ast\nodes\Expression;

/**
 * Rewrite dynamic class constants, e.g. `T::{<expr>}`.
 *
 * @see  https://wiki.php.net/rfc/dynamic_class_constant_fetch
 */
trait RewriteDynamicClassConstants {

  protected function emitScope($result, $scope) {
    if ($scope->member instanceof Expression && -1 !== $scope->line) {
      $result->out->write("constant({$scope->type}::class.'::'.");
      $this->emitOne($result, $scope->member->inline);
      $result->out->write(')');
    } else {
      parent::emitScope($result, $scope);
    }
  }
}