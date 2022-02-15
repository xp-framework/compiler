<?php namespace lang\ast\emit;

use lang\ast\Result;

class GeneratedCode extends Result {
  private $epilog;

  /**
   * Starts a result stream, including an optional prolog and epilog
   *
   * @param io.streams.OutputStream $out
   * @param string $prolog
   * @param string $epilog
   */
  public function __construct($out, $prolog= '', $epilog= '') {
    parent::__construct($out);

    $out->write($prolog);
    $this->epilog= $epilog;
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