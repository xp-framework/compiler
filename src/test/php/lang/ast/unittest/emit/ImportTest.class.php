<?php namespace lang\ast\unittest\emit;

use util\Date;
use lang\XPClass;

class ImportTest extends EmittingTest {

  static function __static() {
    require((new XPClass(self::class))->getClassLoader()->getResourceAsStream('lang/ast/unittest/emit/import.php')->getURI());
  }

  #[@test]
  public function import_type() {
    $this->assertEquals(Date::class, $this->run('
      use util\Date;

      class <T> {
        public function run() { return Date::class; }
      }'
    ));
  }

  #[@test]
  public function import_type_as_alias() {
    $this->assertEquals(Date::class, $this->run('
      use util\Date as D;

      class <T> {
        public function run() { return D::class; }
      }'
    ));
  }

  #[@test]
  public function import_const() {
    $this->assertEquals('imported', $this->run('
      use const lang\ast\unittest\emit\FIXTURE;

      class <T> {
        public function run() { return FIXTURE; }
      }'
    ));
  }

  #[@test]
  public function import_function() {
    $this->assertEquals('imported', $this->run('
      use function lang\ast\unittest\emit\fixture;

      class <T> {
        public function run() { return fixture(); }
      }'
    ));
  }
}