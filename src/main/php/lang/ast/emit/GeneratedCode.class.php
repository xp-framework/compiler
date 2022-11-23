<?php namespace lang\ast\emit;

class GeneratedCode extends Result {
  private $prolog, $epilog;
  public $line= 1;
  public $type= [];

  /**
   * Starts a result stream, including an optional prolog and epilog
   *
   * @param io.streams.OutputStream $out
   * @param string $prolog
   * @param string $epilog
   */
  public function __construct($out, $prolog= '', $epilog= '') {
    $this->prolog= $prolog;
    $this->epilog= $epilog;
    parent::__construct($out);
  }

  /**
   * Initialize result. Guaranteed to be called *once* from constructor.
   * Without implementation here - overwrite in subclasses.
   *
   * @return void
   */
  protected function initialize() {
    '' === $this->prolog || $this->out->write($this->prolog);
  }

  /**
   * Write epilog
   *
   * @return void
   */
  protected function finalize() {
    '' === $this->epilog || $this->out->write($this->epilog);
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
      return $this->type[0]->parent ? $this->lookup($this->type[0]->parent->literal()) : null;
    }

    foreach ($this->type as $enclosing) {
      if ($enclosing->name && $type === $enclosing->name->literal()) return new Declaration($enclosing, $this);
    }

    return new Reflection($type);
  }
}