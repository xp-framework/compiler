<?php namespace lang\ast;

class Result {
  private $id= 0;

  public $out;
  public $line= 1;
  public $meta= [];
  public $locals= [];
  public $stack= [];
  public $call= [];

  /** @param io.streams.Writer */
  public function __construct($out) {
    $this->out= $out;
  }

  /**
   * Creates a temporary variable and returns its name
   *
   * @return string
   */
  public function temp() {
    return '$T'.($this->id++);
  }

  /**
   * Collects emitted code into a buffer and returns it
   *
   * @param  function(lang.ast.Result): void $callable
   * @return string
   */
  protected function buffer($callable) {
    $out= $this->out;
    $buffer= new MemoryOutputStream();
    $this->out= new StringWriter($buffer);

    try {
      $callable($this);
      return $buffer->getBytes();
    } finally {
      $this->out= $out;
    }
  }
}