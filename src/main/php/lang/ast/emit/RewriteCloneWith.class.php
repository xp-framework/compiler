<?php namespace lang\ast\emit;

use lang\ast\nodes\{ArrayLiteral, UnpackExpression};

/** @see https://wiki.php.net/rfc/clone_with_v2 */
trait RewriteCloneWith {

  protected function emitClone($result, $clone) {
    static $wrapper= '(function($c, array $w) { foreach ($w as $p=>$v) { $c->$p=$v; } return $c;})';

    $expr= $clone->arguments['object'] ?? $clone->arguments[0] ?? null;
    $with= $clone->arguments['withProperties'] ?? $clone->arguments[1] ?? null;

    // Built ontop of a wrapper function which iterates over the property-value pairs,
    // assigning them to the clone. Unwind unpack statements, e.g. `clone(...$args)`,
    // into an array, manually unpacking it for invocation.
    if ($expr instanceof UnpackExpression || $with instanceof UnpackExpression) {
      $t= $result->temp();
      $result->out->write('('.$t.'=');
      $this->emitOne($result, new ArrayLiteral($with ? [[null, $expr], [null, $with]] : [[null, $expr]], $clone->line));
      $result->out->write(')?');
      $result->out->write($wrapper.'(clone ('.$t.'["object"] ?? '.$t.'[0]), '.$t.'["withProperties"] ?? '.$t.'[1] ?? [])');
      $result->out->write(':null');
    } else if ($with) {
      $result->out->write($wrapper.'(clone ');
      $this->emitOne($result, $expr);
      $result->out->write(',');
      $this->emitOne($result, $with);
      $result->out->write(')');
    } else {
      $result->out->write('clone ');
      $this->emitOne($result, $expr);
    }
  }
}