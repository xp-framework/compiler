<?php namespace lang\ast\emit;

use lang\ast\nodes\{Signature, Parameter};

/** @see https://wiki.php.net/rfc/clone_with_v2 */
trait RewriteCloneWith {

  protected function emitClone($result, $clone) {
    $expr= $clone->arguments['object'] ?? $clone->arguments[0] ?? null;
    $with= $clone->arguments['withProperties'] ?? $clone->arguments[1] ?? null;

    // Wrap clone with, e.g. clone($x, ['id' => 6100]), inside an IIFE which
    /// iterates over the property-value pairs, assigning them to the clone.
    if ($with) {
      $result->out->write('(function($c, $a) { foreach ($a as $p=>$v) { $c->$p= $v; } return $c;})(clone ');
      $this->emitOne($result, $expr);
      $result->out->write(',');
      $this->emitOne($result, $with);
      $result->out->write(')');
    } else if (isset($clone->arguments['object'])) {
      $result->out->write('clone ');
      $this->emitOne($result, $expr);
    } else {
      return parent::emitClone($result, $clone);
    }
  }
}