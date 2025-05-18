<?php namespace lang\ast\emit;

use lang\ast\nodes\{CallableExpression, CallableNewExpression, Variable};

/**
 * Emulates pipelines
 *
 * @see  https://wiki.php.net/rfc/pipe-operator-v3#precedence
 * @test lang.ast.unittest.emit.PipelinesTest
 */
trait EmulatePipelines {

  protected function emitPipeTarget($result, $target, $arg) {
    if ($target instanceof CallableNewExpression) {
      $target->type->arguments= [new Variable(substr($arg, 1))];
      $this->emitOne($result, $target->type);
      $target->type->arguments= null;
    } else if ($target instanceof CallableExpression) {
      $this->emitOne($result, $target->expression);
      $result->out->write('('.$arg.')');
    } else {
      $result->out->write('(');
      $this->emitOne($result, $target);
      $result->out->write(')('.$arg.')');
    }
  }

  protected function emitPipe($result, $pipe) {

    // $expr |> strtoupper(...) => [$arg= $expr, strtoupper($arg)][1]
    $t= $result->temp();
    $result->out->write('['.$t.'=');
    $this->emitOne($result, $pipe->expression);
    $result->out->write(',');
    $this->emitPipeTarget($result, $pipe->target, $t);
    $result->out->write('][1]');
  }

  protected function emitNullsafePipe($result, $pipe) {

    // $expr ?|> strtoupper(...) => null === ($arg= $expr) ? null : strtoupper($arg)
    $t= $result->temp();
    $result->out->write('null===('.$t.'=');
    $this->emitOne($result, $pipe->expression);
    $result->out->write(')?null:');
    $this->emitPipeTarget($result, $pipe->target, $t);
  }
}