<?php namespace lang\ast\emit;

/**
 * Rewrites throwable expressions as IIFEs
 *
 * @see  https://wiki.php.net/rfc/throw_expression
 */
trait RewriteThrowableExpressions {

  protected function emitThrowExpression($result, $throw) {
    $result->out->write('(');
    $this->enclose($result, $throw->expression, null, false, function($result, $expression) {
      $result->out->write('throw ');
      $this->emitOne($result, $expression);
      $result->out->write(';');
    });
    $result->out->write(')()');
  }
}