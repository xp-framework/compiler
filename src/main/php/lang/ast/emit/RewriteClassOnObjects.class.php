<?php namespace lang\ast\emit;

use lang\ast\nodes\Literal;

/**
 * Rewrites `[expr]::class` to `get_class($object)` except if expression
 * references a type - e.g. `self` or `ClassName`.
 *
 * @see  https://wiki.php.net/rfc/class_name_literal_on_object
 */
trait RewriteClassOnObjects {

  protected function emitScope($result, $scope) {
    if ($scope->member instanceof Literal && 'class' === $scope->member->expression && !is_string($scope->type)) {
      $result->out->write('get_class(');
      $this->emitOne($result, $scope->type);
      $result->out->write(')');
    } else {
      parent::emitScope($result, $scope);
    }
  }
}