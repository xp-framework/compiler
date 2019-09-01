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
    $emitter->handle('nullsafeinstance', function($instance) {
      $t= $this->temp();
      $this->out->write('null === ('.$t.'= ');
      $this->emit($instance->value->expression);
      $this->out->write(') ? null : '.$t.'->');

      if ('name' === $instance->value->member->kind) {
        $this->out->write($instance->value->member->value);
      } else {
        $this->out->write('{');
        $this->emit($instance->value->member);
        $this->out->write('}');
      }
    });
  }
}