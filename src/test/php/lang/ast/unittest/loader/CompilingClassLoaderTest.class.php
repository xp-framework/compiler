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

  /**
   * Loads a class from source
   *
   * @param  string $type
   * @param  string $source
   * @return lang.XPClass
   */
  private function load($type, $source) {
    $f= new File(Environment::tempDir(), $type.'.php');
    FileUtil::setContents($f, sprintf($source, 'ns'.uniqid()));
    $cl= ClassLoader::registerPath($f->getPath());

    $loader= new CompilingClassLoader(self::$runtime);
    try {
      return $loader->loadClass($type);
    } finally {
      ClassLoader::removeLoader($cl);
      $f->unlink();
    }
  }

  #[@test]
  public function can_create() {
    new CompilingClassLoader(self::$runtime);
  }

  #[@test]
  public function load_class() {
    $this->assertEquals('Tests', $this->load('Tests', '<?php namespace %s; class Tests { }')->getSimpleName());
  }

  #[@test, @expect(
  #  class= ClassFormatException::class,
  #  withMessage= 'Syntax error in Errors.php, line 2: Expected ";", have "Syntax"'
  #)]
  public function load_class_with_syntax_errors() {
    $this->load('Errors', "<?php\n<Syntax error in line 2>");
  }
}
