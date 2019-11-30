<?php namespace lang\ast\unittest\emit;

use io\streams\{MemoryOutputStream, StringWriter};
use lang\DynamicClassLoader;
use lang\ast\{CompilingClassLoader, Emitter, Language, Node, Parse, Result, Tokens};
use text\StringTokenizer;
use unittest\Assert;
use unittest\TestCase;
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
    $this->emitter= Emitter::forRuntime('PHP.'.PHP_VERSION)->newInstance();
    foreach ($this->language->extensions() as $extension) {
      $extension->setup($this->language, $this->emitter);
    }
  }

  #[@after]
  public function tearDown() {
    foreach ($this->transformations as $transformation) {
      $this->emitter->remove($transformation);
    }
  }

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
   * Declare a type
   *
   * @param  string $code
   * @return lang.XPClass
   */
  protected function type($code) {
    $name= 'T'.(self::$id++);
    $out= new MemoryOutputStream();

    $parse= new Parse($this->language, new Tokens(new StringTokenizer(str_replace('<T>', $name, $code))), static::class);
    $ast= iterator_to_array($parse->execute());
    if (isset($this->output['ast'])) {
      Console::writeLine();
      Console::writeLine('=== ', static::class, ' ===');
      Console::writeLine($ast);
    }

    $this->emitter->emitAll(new Result(new StringWriter($out)), $ast);
    if (isset($this->output['code'])) {
      Console::writeLine();
      Console::writeLine('=== ', static::class, ' ===');
      Console::writeLine($out->getBytes());
    }

    $this->cl->setClassBytes($name, $out->getBytes());
    return $this->cl->loadClass($name);
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
}