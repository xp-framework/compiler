<?php namespace lang\ast\unittest;

use io\streams\MemoryOutputStream;
use lang\IllegalStateException;
use lang\ast\Element;
use lang\ast\Emitter;
use lang\ast\Result;
use unittest\TestCase;

class EmitterTest extends TestCase {

  private function newEmitter() {
    return Emitter::forRuntime(defined('HHVM_VERSION') ? 'HHVM.'.HHVM_VERSION : 'PHP.'.PHP_VERSION)->newInstance();
  }

  #[@test]
  public function can_create() {
    $this->newEmitter();
  }

  #[@test]
  public function transformations_initially_empty() {
    $this->assertEquals([], $this->newEmitter()->transformations());
  }

  #[@test]
  public function transform() {
    $function= function($class) { return $class; };

    $fixture= $this->newEmitter();
    $fixture->transform('class', $function);
    $this->assertEquals(['class' => [$function]], $fixture->transformations());
  }

  #[@test]
  public function remove() {
    $first= function($class) { return $class; };
    $second= function($class) { $class->annotations['author']= 'Test'; return $class; };

    $fixture= $this->newEmitter();
    $transformation= $fixture->transform('class', $first);
    $fixture->transform('class', $second);
    $fixture->remove($transformation);
    $this->assertEquals(['class' => [$second]], $fixture->transformations());
  }

  #[@test]
  public function remove_unsets_empty_kind() {
    $function= function($class) { return $class; };

    $fixture= $this->newEmitter();
    $transformation= $fixture->transform('class', $function);
    $fixture->remove($transformation);
    $this->assertEquals([], $fixture->transformations());
  }

  #[@test, @expect(IllegalStateException::class)]
  public function emit_element_without_kind() {
    $this->newEmitter()->emitOne(new Result(new MemoryOutputStream()), newinstance(Element::class, [], [
      'line' => -1,
      'kind' => null
    ]));
  }
}