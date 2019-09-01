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

      $node->value= new InstanceExpression($left, $expr);
      $node->kind= 'nullsafeinstance';
      return $node;
    });
    $emitter->handle('nullsafeinstance', function($result, $instance) {
      $t= $result->temp();
      $result->out->write('null === ('.$t.'= ');
      $this->emit($result, $instance->value->expression);
      $result->out->write(') ? null : '.$t.'->');

      if ('name' === $instance->value->member->kind) {
        $result->out->write($instance->value->member->value);
      } else {
        $result->out->write('{');
        $this->emit($result, $instance->value->member);
        $result->out->write('}');
      }
    });
  }
}