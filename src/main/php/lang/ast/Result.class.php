<?php namespace lang\ast;

class Result {
  public $out;
  public $codegen;
  public $line= 1;
  public $meta= [];
  public $locals= [];
  public $stack= [];

  /**
   * Starts an result stream, including a preamble
   *
   * @param io.streams.Writer
   * @param string $preamble
   */
  public function __construct($out, $preamble= '<?php ') {
    $this->out= $out;
    $this->out->write($preamble);
    $this->codegen= new CodeGen();
  }

  /**
   * Creates a temporary variable and returns its name
   *
   * @return string
   */
  public function temp() {
    return '$'.$this->codegen->symbol();
  }
}