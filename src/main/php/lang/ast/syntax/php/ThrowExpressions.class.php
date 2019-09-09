<?php namespace lang\ast\syntax\php;

use lang\ast\nodes\ThrowExpression;
use lang\ast\syntax\Extension;

class ThrowExpressions implements Extension {

  public function setup($language, $emitter) {
    $language->prefix('throw', function($parse, $token) {
      return new ThrowExpression($this->expression($parse, 0), $token->line);
    });
  }
}