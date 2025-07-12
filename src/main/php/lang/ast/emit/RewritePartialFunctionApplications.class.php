<?php namespace lang\ast\emit;

use lang\ast\nodes\{Placeholder, Variable, UnpackExpression};

/**
 * Rewrites partial function application as follows:
 * 
 * ```php
 * // Input:
 * $f= str_replace('test', 'ok', ?);
 * 
 * // Output:
 * $f= fn($arg) => str_replace('test', 'ok', $arg);
 * ```
 *
 * Keeps evaluation order consistent with native implementation:
 *
 * ```php
 * // Input:
 * $f= str_replace('test', result(), ?);
 *
 * // Output:
 * $f= [
 *   $temp= result(),
 *   fn($arg) => str_replace('test', $temp, $arg)
 * ][1];
 * ```
 *
 * @see  https://wiki.php.net/rfc/partial_function_application_v2
 */
trait RewritePartialFunctionApplications {

  protected function emitCallable($result, $callable) {
    if ([Placeholder::$VARIADIC] !== $callable->arguments) {
      $sig= '';
      $pass= $init= [];
      foreach ($callable->arguments as $argument) {
        if (Placeholder::$VARIADIC === $argument) {
          $t= $result->temp();
          $sig.= ',...'.$t;
          $pass[]= new UnpackExpression(new Variable(substr($t, 1)));
        } else if (Placeholder::$ARGUMENT === $argument) {
          $t= $result->temp();
          $sig.= ','.$t;
          $pass[]= new Variable(substr($t, 1));
        } else if ($this->isConstant($result, $argument)) {
          $pass[]= $argument;
        } else {
          $t= $result->temp();
          $pass[]= new Variable(substr($t, 1));
          $init[$t]= $argument;
        }
      }

      // Initialize any non-constant expressions in place
      if ($init) {
        $result->out->write('[');
        foreach ($init as $t => $argument) {
          $result->out->write($t.'=');
          $this->emitOne($result, $argument);
          $result->out->write(',');
        }
      }

      // Emit closure invoking the callable expression
      $result->out->write('fn('.substr($sig, 1).')=>');
      $this->emitOne($result, $callable->expression);
      $result->out->write('(');
      $this->emitArguments($result, $pass);
      $result->out->write(')');
      $init && $result->out->write(']['.sizeof($init).']');
    } else {
      parent::emitCallable($result, $callable);
    }
  }
}