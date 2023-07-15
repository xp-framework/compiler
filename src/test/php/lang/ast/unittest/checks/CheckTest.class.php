<?php namespace lang\ast\unittest\checks;

use io\streams\MemoryOutputStream;
use lang\ast\{Errors, Emitter, Language, Tokens};

abstract class CheckTest {
  private static $id= 0;
  private $language, $emitter;

  /** Constructor */
  public function __construct($output= null) {
    $this->language= Language::named('PHP');
    $this->emitter= Emitter::forRuntime('php:'.PHP_VERSION, [])->newInstance();
    $this->check()->attachTo($this->emitter);
  }

  /** @return lang.ast.checks.Check */
  protected abstract function check();

  protected function compile($code) {
    $name= 'C'.(self::$id++);
    try {
      $this->emitter->write(
        $this->language->parse(new Tokens(str_replace('%T', $name, $code), static::class))->stream(),
        new MemoryOutputStream(),
        '%T'
      );
      return null;
    } catch (Errors $e) {
      return str_replace($name, '%T', $e->diagnostics());
    }
  }
}