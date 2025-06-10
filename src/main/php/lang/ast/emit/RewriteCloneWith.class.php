<?php namespace lang\ast\emit;

use lang\ast\nodes\{Signature, Parameter};

/** @see https://wiki.php.net/rfc/clone_with_v2 */
trait RewriteCloneWith {

  protected function emitClone($result, $clone) {
    $expr= $clone->arguments['object'] ?? $clone->arguments[0] ?? null;
    $with= $clone->arguments['withProperties'] ?? $clone->arguments[1] ?? null;

    // Wrap clone with, e.g. clone($x, ['id' => 6100]), inside an IIFE as follows:
    // `function($args) { $this->id= $args['id']; return $this; }`, then bind
    // this closure to the cloned instance before invoking it with the named
    // arguments so we can access non-public members.
    if ($with) {
      $c= $result->temp();
      $a= $result->temp();

      $result->out->write('['.$c.'=clone ');
      $this->emitOne($result, $expr);
      $result->out->write(','.$a.'=');
      $this->emitOne($result, $with);
      $result->out->write(']?(function($a) { foreach ($a as $p=>$v) { $this->$p= $v; }return $this;})');
      $result->out->write('->bindTo('.$c.','.$c.')('.$a.'):null');
    } else if (isset($clone->arguments['object'])) {
      $result->out->write('clone ');
      $this->emitOne($result, $expr);
    } else {
      return parent::emitClone($result, $clone);
    }
  }
}