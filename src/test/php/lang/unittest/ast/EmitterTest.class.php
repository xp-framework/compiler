<?php namespace lang\unittest\ast;

use lang\ast\Emitter;
use io\streams\MemoryOutputStream;
use io\streams\StringWriter;

class EmitterTest extends \unittest\TestCase {
  private $out;

  /** @return void */
  public function setUp() {
    $this->out= new MemoryOutputStream();
  }

  #[@test]
  public function can_create() {
    new Emitter(new StringWriter($this->out));
  }
}