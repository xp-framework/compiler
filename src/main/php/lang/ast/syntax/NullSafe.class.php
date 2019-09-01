<?php namespace lang\ast\syntax;

use lang\ast\nodes\InstanceExpression;

class NullSafe {

  public function setup($parser, $emitter) {
    $parser->infix('?->', 80, function($node, $left) {
      if ('{' === $this->token->value) {
        $this->token= $this->advance();
        $expr= $this->expression(0);
        $this->token= $this->next('}');
      } else {
        $expr= $this->token;
        $this->token= $this->advance();
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