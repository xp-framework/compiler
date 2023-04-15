<?php namespace lang\ast\emit;

use lang\ast\Node;
use lang\ast\nodes\{Expression, InstanceExpression, ScopeExpression, Literal};

/**
 * Rewrites callable expressions to `Callable::fromClosure()`
 *
 * @see  https://www.php.net/manual/de/closure.fromcallable.php
 * @see  https://wiki.php.net/rfc/first_class_callable_syntax
 */
trait CallablesAsClosures {
  use CallableInstanceMethodReferences { emitCallable as callableInstanceMethodReferences; }

  private function emitQuoted($result, $node) {
    if ($node instanceof Literal) {

      // Rewrite f() => "f"
      $result->out->write('"'.trim($node, '"\'').'"');
    } else if ($node instanceof InstanceExpression) {

      // Rewrite $this->f => [$this, "f"]
      $result->out->write('[');
      $this->emitOne($result, $node->expression);
      $result->out->write(',');
      $this->emitQuoted($result, $node->member);
      $result->out->write(']');
    } else if ($node instanceof ScopeExpression) {

      // Rewrite T::f => [T::class, "f"]
      $result->out->write('[');
      if ($node->type instanceof Node) {
        $this->emitOne($result, $node->type);
      } else {
        $result->out->write($node->type.'::class');
      }
      $result->out->write(',');
      $this->emitQuoted($result, $node->member);
      $result->out->write(']');
    } else if ($node instanceof Expression) {

      // Rewrite T::{<f>} => [T::class, <f>]
      $this->emitOne($result, $node->inline);
    } else {

      // Emit other expressions as-is
      $this->emitOne($result, $node);
    }
  }

  protected function emitCallable($result, $callable) {
    if (
      $callable->expression instanceof InstanceExpression &&
      $callable->expression->expression instanceof Literal
    ) {
      $this->callableInstanceMethodReferences($result, $callable);
      return;
    }

    $result->out->write('\Closure::fromCallable(');
    $this->emitQuoted($result, $callable->expression);
    $result->out->write(')');
  }
}