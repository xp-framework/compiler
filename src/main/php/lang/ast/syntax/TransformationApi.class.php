<?php namespace lang\ast\syntax;

use lang\ast\transform\Transformations;

class TransformationApi implements Extension {

  public function setup($language, $emitter) {
    foreach (Transformations::registered() as $kind => $function) {
      $emitter->transform($kind, $function);
    }
  }
}