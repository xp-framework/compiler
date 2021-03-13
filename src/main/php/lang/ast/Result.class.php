<?php namespace lang\ast;

use lang\ast\emit\{Declaration, Reflection};

class Result {
  public $out;
  public $codegen;
  public $line= 1;
  public $meta= [];
  public $locals= [];
  public $stack= [];
  public $type= [];

  /**
   * Starts a result stream, including a preamble
   *
   * @param io.streams.Writer $out
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

  /**
   * Looks up a given type 
   *
   * @param  string $type
   * @return lang.ast.emit.Type
   */
  public function lookup($type) {
    if ('self' === $type || 'static' === $type || $type === $this->type[0]->name) {
      return new Declaration($this->type[0], $this);
    } else if ('parent' === $type) {
      return $this->lookup($this->type[0]->parent);
    } else {
      return new Reflection($type);
    }
  }
}