<?php namespace lang\ast\unittest\emit;

use io\streams\MemoryOutputStream;
use io\streams\StringWriter;
use lang\DynamicClassLoader;
use lang\ast\Emitter;
use lang\ast\Node;
use lang\ast\Parse;
use lang\ast\Tokens;
use lang\reflect\Package;
use text\StringTokenizer;
use unittest\TestCase;

abstract class EmittingTest extends TestCase {
  private static $cl;
  private static $syntax= [];
  private static $id= 0;

  static function __static() {
    self::$cl= DynamicClassLoader::instanceFor(self::class);
    foreach (Package::forName('lang.ast.syntax')->getClasses() as $class) {
      self::$syntax[]= $class->newInstance();
    }
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
    foreach (self::$syntax as $syntax) {
      $syntax->setup($parse);
    }

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