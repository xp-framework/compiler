<?php namespace lang\ast\emit;

use lang\ast\nodes\Placeholder;

/**
 * Rewrites partial function application as follows:
 * 
 * ```php
 * // Input:
 * $f= str_replace('test', result(), ?);
 * 
 * // Ouput:
 * $f= [
 *   $a0= 'test',
 *   $a1= result(),
 *   fn($arg) => str_replace($a0, $a1, $arg)
 * ][2];
 * ```
 *
 * @see  https://wiki.php.net/rfc/partial_function_application_v2
 */
trait RewritePartialFunctionApplications {

  protected function emitCallable($result, $callable) {
    if ([Placeholder::$VARIADIC] !== $callable->arguments) {
      $sig= $pass= '';
      $offset= 0;
      $result->out->write('[');
      foreach ($callable->arguments as $argument) {
        $t= $result->temp();
        if (Placeholder::$VARIADIC === $argument) {
          $sig.= ',...'.$t;
          $pass.= ',...'.$t;
        } else if (Placeholder::$ARGUMENT === $argument) {
          $sig.= ','.$t;
          $pass.= ','.$t;
        } else {
          $pass.= ','.$t;
          $result->out->write($t.'=');
          $this->emitOne($result, $argument);
          $result->out->write(',');
          $offset++;
        }
      }

      $result->out->write('fn('.substr($sig, 1).')=>');
      $this->emitOne($result, $callable->expression);
      $result->out->write('('.substr($pass, 1).')');

      $result->out->write(']['.$offset.']');
    } else {
      parent::emitCallable($result, $callable);
    }
  }
}