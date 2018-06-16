<?php namespace lang\ast\emit;

use lang\ast\Emitter;

/**
 * HHVM syntax. Like PHP 7.0, but does not allow variadic parameters with
 * types.
 */
class HHVM320 extends Emitter {
  protected $unsupported= [
    'object'   => 72,
    'void'     => 71,
    'iterable' => 71,
    'mixed'    => null,
   ];

  protected function emitParameter($parameter) {
    if ($parameter->variadic) {
      $this->out->write('... $'.$parameter->name);
    } else {
      if ($parameter->type && $t= $this->paramType($parameter->type->literal())) {
        $this->out->write($t.' ');
      }
      $this->out->write(($parameter->reference ? '&' : '').'$'.$parameter->name);
    }
    if ($parameter->default) {
      $this->out->write('=');
      $this->emit($parameter->default);
    }
  }

  protected function emitCatch($catch) {
    $var= $catch->variable ? '$'.$catch->variable : $this->temp();
    if (empty($catch->types)) {
      $this->out->write('catch(\\Throwable '.$var.') {');
    } else {
      $last= array_pop($catch->types);
      $label= sprintf('c%u', crc32($last));
      foreach ($catch->types as $type) {
        $this->out->write('catch('.$type.' '.$var.') { goto '.$label.'; }');
      }
      $this->out->write('catch('.$last.' '.$var.') { '.$label.':');
    }

    $this->emit($catch->body);
    $this->out->write('}');
  }

  protected function emitAssignment($assignment) {
    if ('array' === $assignment->variable->kind) {
      $this->out->write('list(');
      foreach ($assignment->variable->value as $pair) {
        $this->emit($pair[1]);
        $this->out->write(',');
      }
      $this->out->write(')');
      $this->out->write($assignment->operator);
      $this->emit($assignment->expression);
    } else {
      parent::emitAssignment($assignment);
    }
  }

  protected function emitConst($const) {
    $this->out->write('const '.$const->name.'=');
    $this->emit($const->expression);
    $this->out->write(';');
  }
}