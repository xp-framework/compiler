<?php namespace lang\ast\emit;

use lang\ast\nodes\{Signature, Parameter};

/** @see https://wiki.php.net/rfc/clone_with_v2 */
trait RewriteCloneWith {

  protected function emitClone($result, $clone) {
    if (empty($clone->with)) return parent::emitClone($result, $clone);

    // Wrap clone with, e.g. clone($x, id: 6100), inside an IIFE as follows:
    // `function($args) { $this->id= $args['id']; return $this; }`, then bind
    // this closure to the cloned instance before invoking it with the named
    // arguments so we can access non-public members.
    $t= $result->temp();
    $result->out->write('('.$t.'=clone ');
    $this->emitOne($result, $clone->expression);

    $result->out->write(')?(function($a) {');
    foreach ($clone->with as $name => $argument) {
      $result->out->write('$this->'.$name.'=$a["'.$name.'"];');
    }

    $result->out->write('return $this;})->bindTo('.$t.','.$t.')([');
    foreach ($clone->with as $name => $argument) {
      $result->out->write('"'.$name.'"=>');
      $this->emitOne($result, $argument);
      $result->out->write(',');
    }
    $result->out->write(']):null');
  }
}