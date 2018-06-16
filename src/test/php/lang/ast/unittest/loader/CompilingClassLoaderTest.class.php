<?php namespace lang\ast\unittest\loader;

use io\File;
use io\FileUtil;
use lang\ClassFormatException;
use lang\ClassLoader;
use lang\Environment;
use lang\ast\CompilingClassLoader;
use unittest\TestCase;

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
    $f= new File(Environment::tempDir(), 'Errors.php');
    FileUtil::setContents($f, "<?php\nclass A");
    $cl= ClassLoader::registerPath($f->getPath());

    $loader= new CompilingClassLoader(self::$runtime);
    try {
      $loader->loadClass('Errors');
      $this->fail('No exception raised', null, ClassFormatException::class);
    } catch (ClassFormatException $expected) {
      $this->assertEquals('Syntax error in Errors.php, line 1: Expected "{", have "(end)"', $expected->getMessage());
    } finally {
      ClassLoader::removeLoader($cl);
      $f->unlink();
    }
  }
}
