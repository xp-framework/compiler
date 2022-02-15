<?php namespace lang\ast;

use io\streams\OutputStream;
use lang\Closeable;

class Result implements Closeable {
  public $out;
  public $codegen;
  public $line= 1;
  public $meta= [];
  public $locals= [];
  public $stack= [];
  public $type= [];

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
   * Forwards output line to given line number
   *
   * @param  int $line
   * @return self
   */
  public function at($line) {
    if ($line > $this->line) {
      $this->out->write(str_repeat("\n", $line - $this->line));
      $this->line= $line;
    }
    return $this;
  }

  /** @return void */
  public function close() {
    if (null === $this->out) return;

    $this->out->close();
    unset($this->out);
  }
}