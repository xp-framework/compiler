<?php namespace lang\ast\emit;

use lang\ast\nodes\{Literal, Variable};

/**
 * Rewrites $object::class to get_class($object)
 *
 * @see  https://wiki.php.net/rfc/class_name_literal_on_object
 */
trait RewriteClassOnObjects {

  protected function emitScope($result, $scope) {
    if ($scope->type instanceof Variable && $scope->member instanceof Literal && 'class' === $scope->member->expression) {
      $result->out->write('get_class(');
      $this->emitOne($result, $scope->type);
      $result->out->write(')');
    } else {
      parent::emitScope($result, $scope);
    }
  }
}