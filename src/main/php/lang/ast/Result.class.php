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
}