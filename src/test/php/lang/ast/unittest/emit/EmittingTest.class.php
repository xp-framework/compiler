<?php namespace lang\ast\unittest\emit;

use io\streams\MemoryOutputStream;
use lang\DynamicClassLoader;
use lang\ast\emit\GeneratedCode;
use lang\ast\emit\php\XpMeta;
use lang\ast\{CompilingClassLoader, Emitter, Language, Result, Tokens};
use test\{After, Assert, TestCase};
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
    $this->emitter= Emitter::forRuntime($this->runtime(), $this->emitters())->newInstance();
    foreach ($this->language->extensions() as $extension) {
      $extension->setup($this->language, $this->emitter);
    }
  }

  /**
   * Returns emitters to use. Defaults to XpMeta
   *
   * @return string[]
   */
  protected function emitters() { return [XpMeta::class]; }

  /**
   * Returns runtime to use. Uses `PHP_VERSION` constant.
   *
   * @return string
   */
  protected function runtime() { return 'php:'.PHP_VERSION; }

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
    $this->emitter->emitAll(new GeneratedCode($out, ''), $tree->children());
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
    $tree= $this->language->parse(new Tokens(str_replace('<T>', $name, $code), static::class))->tree();
    if (isset($this->output['ast'])) {
      Console::writeLine();
      Console::writeLine('=== ', static::class, ' ===');
      Console::writeLine($tree);
    }

    $out= new MemoryOutputStream();
    $this->emitter->emitAll(new GeneratedCode($out, ''), $tree->children());
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