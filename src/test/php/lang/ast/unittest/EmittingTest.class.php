<?php namespace lang\ast\unittest;

use lang\ast\Emitter;
use io\streams\MemoryOutputStream;
use io\streams\StringWriter;
use lang\DynamicClassLoader;
use text\StringTokenizer;
use lang\ast\Parse;
use lang\ast\Node;
use lang\ast\Tokens;

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
  protected function declare($code) {
    $name= 'T'.(self::$id++);

    $parse= new Parse(new Tokens(new StringTokenizer(str_replace('<T>', $name, $code))));
    $out= new MemoryOutputStream();
    $emit= Emitter::forRuntime(PHP_VERSION)->newInstance(new StringWriter($out));
    $emit->emit($parse->execute());

    self::$cl->setClassBytes($name, $out->getBytes());
    return self::$cl->loadClass($name);
  }
}