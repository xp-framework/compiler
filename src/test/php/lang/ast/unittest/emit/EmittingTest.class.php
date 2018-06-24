<?php namespace lang\ast\unittest\emit;

use io\streams\MemoryOutputStream;
use io\streams\StringWriter;
use lang\DynamicClassLoader;
use lang\ast\Emitter;
use lang\ast\Node;
use lang\ast\Parse;
use lang\ast\Tokens;
use text\StringTokenizer;

abstract class EmittingTest extends \unittest\TestCase {
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

    $parse= new Parse(new Tokens(new StringTokenizer(str_replace('<T>', $name, $code))), $this->getName());
    $out= new MemoryOutputStream();
    $emit= Emitter::forRuntime(defined('HHVM_VERSION') ? 'HHVM.'.HHVM_VERSION : 'PHP.'.PHP_VERSION)->newInstance(new StringWriter($out));
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