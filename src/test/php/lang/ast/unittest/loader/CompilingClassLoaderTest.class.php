<?php namespace lang\ast\unittest\loader;

use io\{File, Files, Folder};
use lang\ast\CompilingClassLoader;
use lang\{ClassFormatException, ClassLoader, ClassNotFoundException, ElementNotFoundException, Environment};
use test\verify\Runtime;
use test\{Action, Assert, Expect, Test, Values};

class CompilingClassLoaderTest {
  private static $runtime;

  static function __static() {
    self::$runtime= 'php:'.PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION.'.'.PHP_RELEASE_VERSION;
  }

  private function tempFolder($structure) {
    $namespace= 'ns'.uniqid();
    $folder= new Folder(Environment::tempDir(), $namespace);
    $folder->exists() || $folder->create();

    $names= [];
    foreach ($structure as $type => $code) {
      Files::write(new File($folder, $type.'.php'), sprintf($code, $namespace));
      $names[$type]= $namespace.'.'.$type;
    }

    return [$folder, $names];
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
    list($folder, $names)= $this->tempFolder($source);

    $cl= ClassLoader::registerPath($folder->path);
    try {
      return $callback(CompilingClassLoader::instanceFor(self::$runtime), $names, $cl);
    } finally {
      ClassLoader::removeLoader($cl);
      $folder->unlink();
    }
  }

  #[Test]
  public function can_create() {
    CompilingClassLoader::instanceFor(self::$runtime);
  }

  #[Test, Values(['7.0.0', '7.0.1', '7.1.0', '7.2.0', '7.3.0', '7.4.0', '7.4.12', '8.0.0'])]
  public function supports_php($version) {
    CompilingClassLoader::instanceFor('php:'.$version);
  }

  #[Test]
  public function string_representation() {
    Assert::equals('CompilingCL<PHP70+lang.ast.emit.php.XpMeta>', CompilingClassLoader::instanceFor('php:7.0.0')->toString());
  }

  #[Test]
  public function hashcode() {
    Assert::equals('CPHP70+lang.ast.emit.php.XpMeta', CompilingClassLoader::instanceFor('php:7.0.0')->hashCode());
  }

  #[Test]
  public function load_class() {
    Assert::equals('Tests', $this->compile(['Tests' => '<?php namespace %s; class Tests { }'], function($loader, $types) {
      return $loader->loadClass($types['Tests'])->getSimpleName();
    }));
  }

  #[Test]
  public function compare() {
    $cl= CompilingClassLoader::instanceFor(self::$runtime);

    Assert::equals(0, $cl->compareTo($cl), 'equals itself');
    Assert::equals(1, $cl->compareTo(null), 'does not equal null');
  }

  #[Test]
  public function instanced_for_augmented() {
    Assert::equals(
      'PHP80+lang.ast.emit.php.XpMeta',
      CompilingClassLoader::instanceFor('php:8.0.0+lang.ast.emit.php.XpMeta')->instanceId()
    );
  }

  #[Test]
  public function package_contents() {
    $contents= $this->compile(['Tests' => '<?php namespace %s; class Tests { }'], function($loader, $types) {
      return $loader->packageContents(strstr($types['Tests'], '.', true));
    });
    Assert::equals(['Tests'.\xp::CLASS_FILE_EXT], $contents);
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
    Assert::match('/<\?php .+ class Tests/', $code);
  }

  #[Test]
  public function load_uri() {
    $class= $this->compile(['Tests' => '<?php namespace %s; class Tests { }'], function($loader, $types, $temp) {
      return $loader->loadUri($temp->path.strtr($types['Tests'], '.', DIRECTORY_SEPARATOR).CompilingClassLoader::EXTENSION);
    });
    Assert::equals('Tests', $class->getSimpleName());
  }

  #[Test, Expect(class: ClassFormatException::class, message: '/Compiler error: Expected "type name", have .+/')]
  public function load_class_with_syntax_errors() {
    $this->compile(['Errors' => "<?php\nclass"], function($loader, $types) {
      return $loader->loadClass($types['Errors']);
    });
  }

  #[Test, Runtime(php: '>=7.3'), Expect(class: ClassFormatException::class, message: '/Compiler error: Class .+ not found/')]
  public function load_class_with_non_existant_parent() {
    $code= "<?php namespace %s;\nclass Orphan extends NotFound { }";
    $this->compile(['Orphan' => $code], function($loader, $types) {
      return $loader->loadClass($types['Orphan']);
    });
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

  #[Test]
  public function does_not_provide_non_existant_uri() {
    Assert::false(CompilingClassLoader::instanceFor(self::$runtime)->providesUri('NotFound.php'));
  }

  #[Test]
  public function does_not_provide_non_existant_resource() {
    Assert::false(CompilingClassLoader::instanceFor(self::$runtime)->providesResource('notfound.md'));
  }

  #[Test]
  public function does_not_provide_non_existant_package() {
    Assert::false(CompilingClassLoader::instanceFor(self::$runtime)->providesPackage('notfound'));
  }

  #[Test, Expect(ClassNotFoundException::class)]
  public function loading_non_existant_uri() {
    CompilingClassLoader::instanceFor(self::$runtime)->loadUri('NotFound.php');
  }

  #[Test, Expect(ClassNotFoundException::class)]
  public function loading_non_existant_class() {
    CompilingClassLoader::instanceFor(self::$runtime)->loadClass('NotFound');
  }

  #[Test, Expect(ClassNotFoundException::class)]
  public function loading_non_existant_class_bytes() {
    CompilingClassLoader::instanceFor(self::$runtime)->loadClassBytes('NotFound');
  }

  #[Test, Expect(ElementNotFoundException::class)]
  public function loading_non_existant_resource() {
    CompilingClassLoader::instanceFor(self::$runtime)->getResource('notfound.md');
  }

  #[Test, Expect(ElementNotFoundException::class)]
  public function loading_non_existant_resource_as_stream() {
    CompilingClassLoader::instanceFor(self::$runtime)->getResourceAsStream('notfound.md');
  }

  #[Test]
  public function ignores_autoload_and_xp_entry() {
    list($folder, $names)= $this->tempFolder([
      '__xp'     => '<?php ...',
      'autoload' => '<?php ...',
      'Fixture'  => '<?php class Fixture { }',
    ]);

    $cl= ClassLoader::registerPath($folder->path);
    try {
      Assert::equals(
        ['Fixture.class.php'],
        CompilingClassLoader::instanceFor(self::$runtime)->packageContents($folder->dirname)
      );
    } finally {
      ClassLoader::removeLoader($cl);
      $folder->unlink();
    }
  }
}