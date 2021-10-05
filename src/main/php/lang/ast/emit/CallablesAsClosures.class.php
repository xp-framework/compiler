<?php namespace lang\ast\emit;

use lang\ast\Node;
use lang\ast\nodes\{InstanceExpression, ScopeExpression, NewExpression, NewClassExpression, Literal};

/**
 * Rewrites callable expressions to `Callable::fromClosure()`
 *
 * @see  https://wiki.php.net/rfc/first_class_callable_syntax
 */
trait CallablesAsClosures {

  protected function emitCallable($result, $callable) {
    if ($callable->expression instanceof NewExpression || $callable->expression instanceof NewClassExpression) {
      $result->out->write('fn(...$_args) => ');
      $this->emitOne($result, $callable->expression);
      return;
    }

    $result->out->write('\Closure::fromCallable(');
    if ($callable->expression instanceof Literal) {

      // Rewrite f() => "f"
      $result->out->write('"'.trim($callable->expression->expression, '"\'').'"');
    } else if ($callable->expression instanceof InstanceExpression) {

      // Rewrite $this->f => [$this, "f"]
      $result->out->write('[');
      $this->emitOne($result, $callable->expression->expression);
      if ($callable->expression->member instanceof Literal) {
        $result->out->write(',"'.trim($callable->expression->member, '"\'').'"');
      } else {
        $result->out->write(',');
        $this->emitOne($result, $callable->expression->member);
      }
      $result->out->write(']');
    } else if ($callable->expression instanceof ScopeExpression) {

      // Rewrite self::f => ["self", "f"]
      $result->out->write('[');
      if ($callable->expression->type instanceof Node) {
        $this->emitOne($result, $callable->expression->type);
      } else {
        $result->out->write('"'.$callable->expression->type.'"');
      }
      if ($callable->expression->member instanceof Literal) {
        $result->out->write(',"'.trim($callable->expression->member, '"\'').'"');
      } else {
        $result->out->write(',');
        $this->emitOne($result, $callable->expression->member);
      }
      $result->out->write(']');
    } else {

      // Emit other expressions as-is
      $this->emitOne($result, $callable->expression);
    }
    $result->out->write(')');
  }
}