<?php namespace lang\ast\syntax;

use lang\ast\transform\Transformations;

class TransformationApi {

  public function setup($language, $emitter) {
    foreach (Transformations::registered() as $kind => $function) {
      $emitter->transform($kind, $function);
    }
  }
}