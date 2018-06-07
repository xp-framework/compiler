<?php namespace lang\ast;

class Error extends \lang\IllegalStateException {

  /**
   * Creates a new error
   *
   * @param  string $message
   * @param  int $line
   */
  public function __construct($message, $line) {
    parent::__construct($message);
    $this->line= $line;
  }
}