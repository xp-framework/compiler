<?php namespace lang\ast\unittest\emit;

use io\streams\MemoryOutputStream;
use lang\ast\Node;
use lang\ast\emit\GeneratedCode;
use unittest\Before;

abstract class EmitterTraitTest {
  private $emitter;

  /** Emits a node and returns the emitted code */
  protected function emit(Node $node, array $type= []): string {
    $result= new GeneratedCode(new MemoryOutputStream(), '');
    $result->type= $type;

    $this->emitter->emitOne($result, $node);
    return $result->out->bytes();
  }

  /** @return lang.ast.Emitter */
  protected abstract function fixture();

  #[Before]
  public function emitter() {
    $this->emitter= $this->fixture();
  }
}