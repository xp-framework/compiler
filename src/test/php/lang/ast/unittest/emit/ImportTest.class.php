<?php namespace lang\ast\unittest\emit;

use Traversable, Iterator;
use lang\XPClass;
use test\{Assert, Test};
use util\Date;

class ImportTest extends EmittingTest {

  static function __static() {
    require((new XPClass(self::class))->getClassLoader()->getResourceAsStream('lang/ast/unittest/emit/import.php')->getURI());
  }

  #[Test]
  public function import_type() {
    Assert::equals(Date::class, $this->run('
      use util\Date;

      class %T {
        public function run() { return Date::class; }
      }'
    ));
  }

  #[Test]
  public function import_type_as_alias() {
    Assert::equals(Date::class, $this->run('
      use util\Date as D;

      class %T {
        public function run() { return D::class; }
      }'
    ));
  }

  #[Test]
  public function import_const() {
    Assert::equals('imported', $this->run('
      use const lang\ast\unittest\emit\FIXTURE;

      class %T {
        public function run() { return FIXTURE; }
      }'
    ));
  }

  #[Test]
  public function import_function() {
    Assert::equals('imported', $this->run('
      use function lang\ast\unittest\emit\fixture;

      class %T {
        public function run() { return fixture(); }
      }'
    ));
  }

  #[Test]
  public function import_global_into_namespace() {
    Assert::equals(Traversable::class, $this->run('namespace test;
      use Traversable;

      class %T {
        public function run() { return Traversable::class; }
      }'
    ));
  }

  #[Test]
  public function import_globals_into_namespace() {
    Assert::equals([Traversable::class, Iterator::class], $this->run('namespace test;
      use Traversable, Iterator;

      class %T {
        public function run() { return [Traversable::class, Iterator::class]; }
      }'
    ));
  }
}