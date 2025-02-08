<?php namespace lang\ast\unittest\emit;

use test\{Assert, Test};
use util\Date;

class NamespacesTest extends EmittingTest {

  #[Test]
  public function without_namespace() {
    Assert::false(strpos($this->declare('class %T { }')->name(), '.'));
  }

  #[Test]
  public function with_namespace() {
    Assert::equals('test', $this->declare('namespace test; class %T { }')->package()->name());
  }

  #[Test]
  public function resolves_unqualified() {
    $r= $this->run('namespace util; class %T {
      public function run() {
        return new Date("1977-12-14");
      }
    }');
    Assert::equals(new Date('1977-12-14'), $r);
  }

  #[Test]
  public function resolves_relative() {
    $r= $this->run('class %T {
      public function run() {
        return new util\Date("1977-12-14");
      }
    }');
    Assert::equals(new Date('1977-12-14'), $r);
  }

  #[Test]
  public function resolves_absolute() {
    $r= $this->run('namespace test; class %T {
      public function run() {
        return new \util\Date("1977-12-14");
      }
    }');
    Assert::equals(new Date('1977-12-14'), $r);
  }

  #[Test]
  public function resolves_import() {
    $r= $this->run('namespace test; use util\Date; class %T {
      public function run() {
        return new Date("1977-12-14");
      }
    }');
    Assert::equals(new Date('1977-12-14'), $r);
  }

  #[Test]
  public function resolves_alias() {
    $r= $this->run('namespace test; use util\Date as DateTime; class %T {
      public function run() {
        return new DateTime("1977-12-14");
      }
    }');
    Assert::equals(new Date('1977-12-14'), $r);
  }

  #[Test]
  public function resolves_namespace_keyword() {
    $r= $this->run('namespace util; class %T {
      public function run() {
        return new namespace\Date("1977-12-14");
      }
    }');
    Assert::equals(new Date('1977-12-14'), $r);
  }

  #[Test]
  public function resolves_sub_namespace() {
    $r= $this->run('class %T {
      public function run() {
        return new namespace\util\Date("1977-12-14");
      }
    }');
    Assert::equals(new Date('1977-12-14'), $r);
  }
}