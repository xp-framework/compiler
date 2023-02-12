<?php namespace lang\ast\emit;

class GeneratedCode extends Result {
  private $prolog, $epilog;
  public $line= 1;

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
    $enclosing= $this->codegen->scope[0] ?? null;

    if ('self' === $type || 'static' === $type) {
      return new Declaration($enclosing->type, $this);
    } else if ('parent' === $type) {
      return $enclosing->type->parent ? $this->lookup($enclosing->type->parent->literal()) : null;
    }

    foreach ($this->codegen->scope as $scope) {
      if ($scope->type->name && $type === $scope->type->name->literal()) {
        return new Declaration($scope->type, $this);
      }
    }

    if (class_exists($type) || interface_exists($type) || trait_exists($type) || enum_exists($type)) {
      return new Reflection($type);
    } else {
      return new Incomplete($type);
    }
  }
}