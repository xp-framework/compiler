<?php namespace lang\ast\emit;

use lang\ast\nodes\{Placeholder, Variable, UnpackExpression};

/**
 * Rewrites partial function application
 *
 * @see  https://wiki.php.net/rfc/partial_function_application_v2
 */
trait RewritePartialFunctionApplications {

  protected function emitCallable($result, $callable) {
    if ([Placeholder::$VARIADIC] !== $callable->arguments) {
      $pass= [];
      $sig= '';
      foreach ($callable->arguments as $argument) {
        if (Placeholder::$VARIADIC === $argument) {
          $t= $result->temp();
          $pass[]= new UnpackExpression(new Variable(substr($t, 1)));
          $sig.= ',... '.$t;
        } else if (Placeholder::$ARGUMENT === $argument) {
          $t= $result->temp();
          $pass[]= new Variable(substr($t, 1));
          $sig.= ','.$t;
        } else {
          $pass[]= $argument;
        }
      }
      $result->out->write('fn('.substr($sig, 1).')=>');

      $this->emitOne($result, $callable->expression);
      $result->out->write('(');
      $this->emitArguments($result, $pass);
      $result->out->write(')');
    } else {
      parent::emitCallable($result, $callable);
    }
  }
}