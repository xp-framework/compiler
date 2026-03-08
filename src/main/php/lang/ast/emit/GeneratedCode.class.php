<?php namespace lang\ast\emit;

class GeneratedCode extends Result {
  private $prolog, $epilog;

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
   * Creates a temporary variable and returns its name
   *
   * @return string
   */
  public function temp() {
    return '$'.$this->codegen->symbol();
  }
}