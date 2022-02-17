<?php namespace lang\ast\emit;

use io\streams\OutputStream;
use lang\Closeable;
use lang\ast\CodeGen;

class Result implements Closeable {
  public $out;
  public $codegen;
  public $meta= [];
  public $locals= [];
  public $stack= [];

  /**
   * Starts a result stream, including an optional prolog and epilog
   *
   * @param io.streams.OutputStream $out
   */
  public function __construct(OutputStream $out) {
    $this->out= $out;
    $this->codegen= new CodeGen();
  }

  /**
   * Finalize result. Guaranteed to be called *once* from within `close()`.
   * Without implementation here - overwrite in subclasses.
   *
   * @return void
   */
  protected function finalize() {
    // NOOP
  }

  /** @return void */
  public function close() {
    if (null === $this->out) return;

    $this->finalize();
    $this->out->close();
    unset($this->out);
  }
}