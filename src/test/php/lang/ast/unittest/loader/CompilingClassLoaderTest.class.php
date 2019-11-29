<?php namespace lang\ast\unittest\loader;

use io\{File, FileUtil, Folder};
use lang\{ClassFormatException, ClassLoader, Environment};
use lang\ast\CompilingClassLoader;
use unittest\TestCase;

class CompilingClassLoaderTest extends TestCase {
  private static $runtime;

  static function __static() {
    self::$runtime= 'PHP.'.PHP_VERSION;
  }

  /**
   * Loads a class from source
   *
   * @param  string $type
   * @param  string $source
   * @return lang.XPClass
   */
  private function load($type, $source) {
    $namespace= 'ns'.uniqid();
    $folder= new Folder(Environment::tempDir(), $namespace);
    $folder->exists() || $folder->create();

    FileUtil::setContents(new File($folder, $type.'.php'), sprintf($source, $namespace));
    $cl= ClassLoader::registerPath($folder->path);

    $loader= CompilingClassLoader::instanceFor(self::$runtime);
    try {
      return $loader->loadClass($namespace.'.'.$type);
    } finally {
      ClassLoader::removeLoader($cl);
      $folder->unlink();
    }
  }

  #[@test]
  public function can_create() {
    CompilingClassLoader::instanceFor(self::$runtime);
  }

  #[@test]
  public function load_class() {
    $this->assertEquals('Tests', $this->load('Tests', '<?php namespace %s; class Tests { }')->getSimpleName());
  }

  #[@test, @expect([
  #  'class' => ClassFormatException::class,
  #  'withMessage' => 'Compiler error: Expected "{", have "(end)"'
  #])]
  public function load_class_with_syntax_errors() {
    $this->load('Errors', "<?php\nclass");
  }

  #[@test]
  public function triggered_errors_filename() {
    $t= $this->load('Triggers', '<?php namespace %s; class Triggers { 
      public function trigger() {
        trigger_error("Test");
      }
    }');

    $t->newInstance()->trigger();
    $this->assertNotEquals(false, strpos(
      preg_replace('#^.+://#', '', key(\xp::$errors)),
      strtr($t->getName(), '.', DIRECTORY_SEPARATOR).'.php'
    ));
    \xp::gc();
  }
}