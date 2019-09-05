<?php namespace lang\ast\unittest\emit;

use io\streams\MemoryOutputStream;
use io\streams\StringWriter;
use lang\DynamicClassLoader;
use lang\ast\CompilingClassLoader;
use lang\ast\Emitter;
use lang\ast\Language;
use lang\ast\Node;
use lang\ast\Parse;
use lang\ast\Result;
use lang\ast\Tokens;
use text\StringTokenizer;
use unittest\TestCase;

abstract class EmittingTest extends TestCase {
  private static $cl, $language, $emitter;
  private static $id= 0;

  #[@beforeClass]
  public static function setupCompiler() {
    self::$cl= DynamicClassLoader::instanceFor(self::class);
    self::$language= Language::named('PHP');
    self::$emitter= Emitter::forRuntime(defined('HHVM_VERSION') ? 'HHVM.'.HHVM_VERSION : 'PHP.'.PHP_VERSION)->newInstance();
    foreach (self::$language->extensions() as $extension) {
      $extension->setup(self::$language, self::$emitter);
    }
  }

  protected function transform($type, $function) {
    self::$emitter->transform($type, $function);
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

    $parse= new Parse(self::$language, new Tokens(new StringTokenizer(str_replace('<T>', $name, $code))), $this->getName());
    self::$emitter->emitAll(new Result(new StringWriter($out)), $parse->execute());

    // var_dump($out->getBytes());
    self::$cl->setClassBytes($name, $out->getBytes());
    return self::$cl->loadClass($name);
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