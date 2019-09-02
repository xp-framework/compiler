<?php namespace lang\ast\syntax;

use lang\ast\nodes\InstanceExpression;

class NullSafe {

  public function setup($language, $emitter) {
    $language->infix('?->', 80, function($parse, $node, $left) {
      if ('{' === $parse->token->value) {
        $parse->forward();
        $expr= $this->expression($parse, 0);
        $parse->expecting('}', 'dynamic member');
      } else {
        $expr= $parse->token;
        $parse->forward();
      }

      $value= new InstanceExpression($left, $expr);
      $value->kind= 'nullsafeinstance';
      return $value;
    });
    $emitter->handle('nullsafeinstance', function($result, $instance) {
      $t= $result->temp();
      $result->out->write('null === ('.$t.'= ');
      $this->emit($result, $instance->expression);
      $result->out->write(') ? null : '.$t.'->');

      if ('name' === $instance->member->kind) {
        $result->out->write($instance->member->value);
      } else {
        $result->out->write('{');
        $this->emit($result, $instance->member);
        $result->out->write('}');
      }
    });
  }
}