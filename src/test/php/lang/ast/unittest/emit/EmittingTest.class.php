<?php namespace lang\ast\unittest\emit;

use io\streams\MemoryOutputStream;
use io\streams\StringWriter;
use lang\DynamicClassLoader;
use lang\ast\CompilingClassLoader;
use lang\ast\Emitter;
use lang\ast\Language;
use lang\ast\Node;
use lang\ast\Parse;
use lang\ast\Tokens;
use text\StringTokenizer;
use unittest\TestCase;

abstract class EmittingTest extends TestCase {
  private static $cl;
  private static $id= 0;

  static function __static() {
    self::$cl= DynamicClassLoader::instanceFor(self::class);
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

    $parse= new Parse(Language::named('PHP'), new Tokens(new StringTokenizer(str_replace('<T>', $name, $code))), $this->getName());
    $emit= Emitter::forRuntime(defined('HHVM_VERSION') ? 'HHVM.'.HHVM_VERSION : 'PHP.'.PHP_VERSION)->newInstance(new StringWriter($out));
    foreach (CompilingClassLoader::$syntax as $syntax) {
      $syntax->setup($parse->language, $emit);
    }

    $emit->emit($parse->execute());
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