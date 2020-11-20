<?php namespace lang\ast\unittest\loader;

use io\{File, FileUtil, Folder};
use lang\ast\CompilingClassLoader;
use lang\{ClassFormatException, ClassLoader, Environment};
use unittest\{Assert, Expect, Test, TestCase};

class CompilingClassLoaderTest {
  private static $runtime;

  static function __static() {
    self::$runtime= 'PHP.'.PHP_VERSION;
  }

  /**
   * Sets us compiling class loader with a given type and source code, then
   * executes callback.
   *
   * @param  [:string] $source
   * @param  function(lang.IClassLoader, string): var $callback
   * @return var
   */
  private function compile($source, $callback) {
    $namespace= 'ns'.uniqid();
    $folder= new Folder(Environment::tempDir(), $namespace);
    $folder->exists() || $folder->create();

    $names= [];
    foreach ($source as $type => $code) {
      FileUtil::setContents(new File($folder, $type.'.php'), sprintf($code, $namespace));
      $names[$type]= $namespace.'.'.$type;
    }
    $cl= ClassLoader::registerPath($folder->path);

    $loader= CompilingClassLoader::instanceFor(self::$runtime);
    try {
      return $callback($loader, $names);
    } finally {
      ClassLoader::removeLoader($cl);
      $folder->unlink();
    }
  }

  #[Test]
  public function can_create() {
    CompilingClassLoader::instanceFor(self::$runtime);
  }

  #[Test]
  public function load_class() {
    Assert::equals('Tests', $this->compile(['Tests' => '<?php namespace %s; class Tests { }'], function($loader, $types) {
      return $loader->loadClass($types['Tests'])->getSimpleName();
    }));
  }

  #[Test]
  public function load_dependencies() {
    $source= [
      'Child'   => '<?php namespace %s; class Child extends Base implements Impl { use Feature; }',
      'Base'    => '<?php namespace %s; class Base { }',
      'Impl'    => '<?php namespace %s; interface Impl { }',
      'Feature' => '<?php namespace %s; trait Feature { }'
    ];

    $c= $this->compile($source, function($loader, $types) { return $loader->loadClass($types['Child']); });
    $n= function($c) { return $c->getSimpleName(); };
    Assert::equals(
      ['Child', 'Base', ['Impl'], ['Feature']],
      [$n($c), $n($c->getParentClass()), array_map($n, $c->getInterfaces()), array_map($n, $c->getTraits())]
    );
  }

  #[Test]
  public function load_class_bytes() {
    $code= $this->compile(['Tests' => '<?php namespace %s; class Tests { }'], function($loader, $types) {
      return $loader->loadClassBytes($types['Tests']);
    });
    Assert::true((bool)preg_match('/<\?php .+ class Tests/', $code));
  }

  #[Test, Expect(['class' => ClassFormatException::class, 'withMessage' => 'Compiler error: Expected "{", have "(end)"'])]
  public function load_class_with_syntax_errors() {
    $this->compile(['Errors' => "<?php\nclass"], function($loader, $types) { return $loader->loadClass($types['Errors']); });
  }

  #[Test]
  public function triggered_errors_filename() {
    $source= ['Triggers' => '<?php namespace %s; class Triggers {
      public function trigger() {
        trigger_error("Test");
      }
    }'];
    $t= $this->compile($source, function($loader, $types) { return $loader->loadClass($types['Triggers']); });

    $t->newInstance()->trigger();
    Assert::notEquals(false, strpos(
      preg_replace('#^.+://#', '', key(\xp::$errors)),
      strtr($t->getName(), '.', DIRECTORY_SEPARATOR).'.php'
    ));
    \xp::gc();
  }
}