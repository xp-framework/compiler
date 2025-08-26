<?php namespace lang\ast\emit;

use lang\ast\nodes\{CallableExpression, CallableNewExpression, Literal, Placeholder, Variable};

/**
 * Emulates pipelines / the pipe operator, including a null-safe version.
 *
 * ```php
 * // Enclose expressions as follows:
 * $in |> $expr;
 * ($expr)($in);
 *
 * // Optimize for string literals:
 * $in |> 'strlen';
 * strlen($in);
 *
 * // Optimize for first-class callables with single placeholder argument:
 * $in |> strlen(...);
 * strlen($in);
 *
 * // Optimize for partial functions with single placeholder argument:
 * $in |> str_replace('test', 'ok', ?);
 * strlen('test', 'ok', $in);
 * ```
 *
 * @see  https://wiki.php.net/rfc/pipe-operator-v3
 * @see  https://externals.io/message/107661#107670
 * @test lang.ast.unittest.emit.EmulatePipelinesTest
 * @test lang.ast.unittest.emit.PipelinesTest
 */
trait EmulatePipelines {

  private function passSingle($arguments, $arg) {
    $placeholder= -1;
    foreach ($arguments as $n => $argument) {
      if ($argument instanceof Placeholder) {
        if ($placeholder > -1) return null;
        $placeholder= $n;
      }
    }

    $r= $arguments;
    $r[$placeholder]= $arg;
    return $r;
  }

  protected function emitPipeTarget($result, $target, $arg) {
    if ($target instanceof CallableNewExpression && ($pass= $this->passSingle($target->arguments, $arg))) {
      $target->type->arguments= $pass;
      $this->emitOne($result, $target->type);
      $target->type->arguments= null;
    } else if ($target instanceof CallableExpression && ($pass= $this->passSingle($target->arguments, $arg))) {
      $this->emitOne($result, $target->expression);
      $result->out->write('(');
      $this->emitArguments($result, $pass);
      $result->out->write(')');
    } else if ($target instanceof Literal) {
      $result->out->write(trim($target->expression, '"\''));
      $result->out->write('(');
      $this->emitOne($result, $arg);
      $result->out->write(')');
    } else {
      $result->out->write('(');
      $this->emitOne($result, $target);
      $result->out->write(')(');
      $this->emitOne($result, $arg);
      $result->out->write(')');
    }
  }

  protected function emitPipe($result, $pipe) {

    // <const> |> strtoupper(...) => strtoupper(<const>)
    // <expr> |> strtoupper(...) => [$arg= <expr>, strtoupper($arg)][1]
    if ($this->isConstant($result, $pipe->expression)) {
      $this->emitPipeTarget($result, $pipe->target, $pipe->expression);
    } else {
      $t= $result->temp();
      $result->out->write('['.$t.'=');
      $this->emitOne($result, $pipe->expression);
      $result->out->write(',');
      $this->emitPipeTarget($result, $pipe->target, new Variable(substr($t, 1)));
      $result->out->write('][1]');
    }
  }

  protected function emitNullsafePipe($result, $pipe) {

    // <expr> ?|> strtoupper(...) => null === ($arg= <expr>) ? null : strtoupper($arg)
    $t= $result->temp();
    $result->out->write('null===('.$t.'=');
    $this->emitOne($result, $pipe->expression);
    $result->out->write(')?null:');
    $this->emitPipeTarget($result, $pipe->target, new Variable(substr($t, 1)));
  }
}