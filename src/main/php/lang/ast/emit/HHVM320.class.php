<?php namespace lang\ast\emit;

use lang\ast\Emitter;

/**
 * HHVM syntax. Like PHP 7.0, but does not allow variadic parameters with
 * types.
 */
class HHVM320 extends Emitter {
  use OmitPropertyTypes, OmitConstModifiers;
  use RewriteNullCoalesceAssignment, RewriteLambdaExpressions, RewriteMultiCatch;

  protected $unsupported= [
    'object'   => 72,
    'void'     => 71,
    'iterable' => 71,
    'mixed'    => null,
   ];

  protected function emitParameter($result, $parameter) {
    if ($parameter->variadic) {
      $result->out->write('... $'.$parameter->name);
      $result->locals[$parameter->name]= true;
    } else {
      parent::emitParameter($result$parameter);
    }
  }
}