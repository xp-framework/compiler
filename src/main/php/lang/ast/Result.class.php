<?php namespace lang\ast;

use lang\Closeable;
use lang\ast\emit\{Declaration, Reflection};

class Result implements Closeable {
  public $out;
  public $codegen;
  public $line= 1;
  public $meta= [];
  public $locals= [];
  public $stack= [];
  public $type= [];
  private $epilog;

  /**
   * Starts a result stream, including an optional prolog and epilog
   *
   * @param io.streams.Writer $out
   * @param string $prolog
   * @param string $epilog
   */
  public function __construct($out, $prolog= '', $epilog= '') {
    $this->out= $out;
    $this->out->write($prolog);
    $this->epilog= $epilog;
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

  /**
   * Looks up a given type 
   *
   * @param  string $type
   * @return lang.ast.emit.Type
   */
  public function lookup($type) {
    if ('self' === $type || 'static' === $type) {
      return new Declaration($this->type[0], $this);
    } else if ('parent' === $type) {
      return $this->lookup($this->type[0]->parent);
    }

    foreach ($this->type as $enclosing) {
      if ($type === $enclosing->name) return new Declaration($enclosing, $this);
    }

    return new Reflection($type);
  }

  /** @return void */
  public function close() {
    if (null === $this->out) return;

    // Write epilog, then close and ensure this doesn't happen twice
    '' === $this->epilog || $this->out->write($this->epilog);
    $this->out->close();
    unset($this->out);
  }
}