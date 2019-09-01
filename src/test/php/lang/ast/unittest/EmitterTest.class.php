<?php namespace lang\ast\unittest;

use lang\ast\Emitter;
use unittest\TestCase;

class EmitterTest extends TestCase {

  #[@test]
  public function can_create() {
    Emitter::forRuntime(defined('HHVM_VERSION') ? 'HHVM.'.HHVM_VERSION : 'PHP.'.PHP_VERSION)->newInstance();
  }
}