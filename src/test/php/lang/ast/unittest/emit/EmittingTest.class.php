<?php namespace lang\ast\unittest\emit;

use io\streams\{MemoryOutputStream, StringWriter};
use lang\DynamicClassLoader;
use lang\ast\{CompilingClassLoader, Emitter, Language, Result, Tokens};
use unittest\{After, Assert, TestCase};
use util\cmd\Console;

abstract class EmittingTest {
  private static $id= 0;
  private $cl, $language, $emitter, $output;
  private $transformations= [];

  /**
   * Constructor
   *
   * @param  ?string $output E.g. `ast,code` to dump both AST and emitted code
   */
  public function __construct($output= null) {
    $this->output= $output ? array_flip(explode(',', $output)) : [];
    $this->cl= DynamicClassLoader::instanceFor(self::class);
    $this->language= Language::named('PHP');
    $this->emitter= Emitter::forRuntime($this->runtime())->newInstance();
    foreach ($this->language->extensions() as $extension) {
      $extension->setup($this->language, $this->emitter);
    }
  }

  /**
   * Returns runtime to use. Uses `PHP_VERSION` constant.
   *
   * @return string
   */
  protected function runtime() { return 'PHP.'.PHP_VERSION; }

  /**
   * Register a transformation. Will take care of removing it on test shutdown.
   *
   * @param  string $kind
   * @param  function(lang.ast.Node): lang.ast.Node|iterable $function
   * @return void
   */
  protected function transform($type, $function) {
    $this->transformations[]= $this->emitter->transform($type, $function);
  }

  /**
   * Parse and emit given code
   *
   * @param  string $code
   * @return string
   */
  protected function emit($code) {
    $name= 'E'.(self::$id++);
    $tree= $this->language->parse(new Tokens(str_replace('<T>', $name, $code), static::class))->tree();

    $out= new MemoryOutputStream();
    $this->emitter->emitAll(new Result(new StringWriter($out), ''), $tree->children());
    return $out->bytes();
  }

  /**
   * Declare a type
   *
   * @param  string $code
   * @return lang.XPClass
   */
  protected function type($code) {
    $name= 'T'.(self::$id++);
    $out= new MemoryOutputStream();

    $tree= $this->language->parse(new Tokens(str_replace('<T>', $name, $code), static::class))->tree();
    if (isset($this->output['ast'])) {
      Console::writeLine();
      Console::writeLine('=== ', static::class, ' ===');
      Console::writeLine($tree);
    }

    $this->emitter->emitAll(new Result(new StringWriter($out), ''), $tree->children());
    if (isset($this->output['code'])) {
      Console::writeLine();
      Console::writeLine('=== ', static::class, ' ===');
      Console::writeLine($out->bytes());
    }

    $class= ($package= $tree->scope()->package) ? strtr(substr($package, 1), '\\', '.').'.'.$name : $name;
    $this->cl->setClassBytes($class, $out->bytes());
    return $this->cl->loadClass($class);
  }

  /**
   * Run code
   *
   * @param  string $code
   * @param  var... $args
   * @return var
   */
  protected function run($code, ... $args) {
    return $this->type($code)->newInstance()->run(...$args);
  }

  #[After]
  public function tearDown() {
    foreach ($this->transformations as $transformation) {
      $this->emitter->remove($transformation);
    }
  }
}