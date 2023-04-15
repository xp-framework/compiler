<?php namespace lang\ast\emit;

use lang\ast\nodes\{InstanceExpression, Literal};

/**
 * Rewrites `T->func(...)` to `fn(T $t) => $t->func()`.
 *
 * @see  https://externals.io/message/120011
 */
trait CallableInstanceMethodReferences {

  protected function emitCallable($result, $callable) {
    if (
      $callable->expression instanceof InstanceExpression &&
      $callable->expression->expression instanceof Literal
    ) {
      $type= $callable->expression->expression->expression;
      $result->out->write('static function('.$type.' $_) { return $_->');
      $this->emitOne($result, $callable->expression->member);
      $result->out->write('(); }');
    } else {
      return parent::emitCallable($result, $callable);
    }
  }
}

