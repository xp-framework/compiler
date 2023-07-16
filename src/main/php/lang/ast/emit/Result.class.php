<?php namespace lang\ast\emit;

use io\streams\OutputStream;
use lang\Closeable;
use lang\ast\CodeGen;

class Result implements Closeable {
  public $out;
  public $codegen;
  public $locals= [];
  public $stack= [];

  /**
   * Starts a result stream.
   *
   * @param io.streams.OutputStream $out
   */
  public function __construct(OutputStream $out) {
    $this->out= $out;
    $this->codegen= new CodeGen();
    $this->initialize();
  }

  /**
   * Set filename this result originates from, defaulting to `(unknown)`.
   *
   * @param  ?string $file
   * @return self
   */
  public function from($file) {
    $this->codegen->source= $file ?? '(unknown)';
    return $this;
  }


  /**
   * Initialize result. Guaranteed to be called *once* from constructor.
   * Without implementation here - overwrite in subclasses.
   *
   * @return void
   */
  protected function initialize() {
    // NOOP
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