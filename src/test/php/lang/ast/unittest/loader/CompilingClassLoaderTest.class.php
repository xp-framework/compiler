<?php namespace lang\ast\unittest\loader;

use unittest\TestCase;
use lang\ast\CompilingClassLoader;
use lang\ClassFormatException;
use lang\DynamicClassLoader;
use lang\ClassLoader;
use io\File;
use io\streams\MemoryInputStream;

class CompilingClassLoaderTest extends TestCase {
  private static $runtime;

  static function __static() {
    self::$runtime= defined('HHVM_VERSION') ? 'HHVM.'.HHVM_VERSION : 'PHP.'.PHP_VERSION;
  }

  #[@test]
  public function can_create() {
    new CompilingClassLoader(self::$runtime);
  }

  #[@test]
  public function load_class() {
    $loader= new CompilingClassLoader(self::$runtime);
    $this->assertEquals('Tests', $loader->loadClass('lang.ast.unittest.loader.Tests')->getSimpleName());
  }

  #[@test]
  public function load_class_with_syntax_errors() {
    $cl= ClassLoader::registerLoader(@newinstance(DynamicClassLoader::class, [], [
      'providesResource'    => function($file) {
        return 'Errors.php' === $file;
      },
      'getResourceAsStream' => function($file) {
        return newinstance(File::class, ['.'], [
          'in' => function() { return new MemoryInputStream("<?php\n<Syntax error in line 2>"); }
        ]);
      }
    ]));

    $loader= new CompilingClassLoader(self::$runtime);
    try {
      $loader->loadClass('Errors');
      $this->fail('No exception raised', null, ClassFormatException::class);
    } catch (ClassFormatException $expected) {
      $this->assertEquals('Syntax error in Errors.php, line 2: Expected ";", have "Syntax"', $expected->getMessage());
    } finally {
      ClassLoader::removeLoader($cl);
    }
  }
}
