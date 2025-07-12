<?php namespace lang\ast\emit;

use lang\ast\nodes\{CallableExpression, CallableNewExpression, Variable, Placeholder};

/**
 * Emulates pipelines / the pipe operator, including a null-safe version.
 *
 * ```php
 * // Enclose expressions as follows:
 * $in |> $expr;
 * ($expr)($in);
 *
 * // Optimize for first-class callables with single placeholder argument:
 * $in |> strlen(...);
 * strlen($in);
 * ```
 *
 * @see  https://wiki.php.net/rfc/pipe-operator-v3
 * @see  https://externals.io/message/107661#107670
 * @test lang.ast.unittest.emit.PipelinesTest
 */
trait EmulatePipelines {

  private function singlePlaceholder($arguments) {
    return 1 === sizeof($arguments) && $arguments[0] instanceof Placeholder;
  }

  protected function emitPipeTarget($result, $target, $arg) {
    if ($target instanceof CallableNewExpression && $this->singlePlaceholder($target->arguments)) {
      $target->type->arguments= [new Variable(substr($arg, 1))];
      $this->emitOne($result, $target->type);
      $target->type->arguments= null;
    } else if ($target instanceof CallableExpression && $this->singlePlaceholder($target->arguments)) {
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