<?php namespace lang\ast;

class Error extends \lang\IllegalStateException {

  /**
   * Creates a new error
   *
   * @param  string $message
   * @param  string $file
   * @param  int $line
   */
  public function __construct($message, $file, $line) {
    parent::__construct($message);
    $this->file= $file;
    $this->line= $line;
  }
}