<?php namespace lang\ast\unittest\emit;

use unittest\{Assert, Test};
use util\Date;

class NamespacesTest extends EmittingTest {

  #[Test]
  public function without_namespace() {
    Assert::equals('', $this->type('class <T> { }')->getPackage()->getName());
  }

  #[Test]
  public function with_namespace() {
    Assert::equals('test', $this->type('namespace test; class <T> { }')->getPackage()->getName());
  }

  #[Test]
  public function resolves_unqualified() {
    $r= $this->run('namespace util; class <T> {
      public function run() {
        return new Date("1977-12-14");
      }
    }');
    Assert::equals(new Date('1977-12-14'), $r);
  }

  #[Test]
  public function resolves_relative() {
    $r= $this->run('class <T> {
      public function run() {
        return new util\Date("1977-12-14");
      }
    }');
    Assert::equals(new Date('1977-12-14'), $r);
  }

  #[Test]
  public function resolves_absolute() {
    $r= $this->run('namespace test; class <T> {
      public function run() {
        return new \util\Date("1977-12-14");
      }
    }');
    Assert::equals(new Date('1977-12-14'), $r);
  }

  #[Test]
  public function resolves_import() {
    $r= $this->run('namespace test; use util\Date; class <T> {
      public function run() {
        return new Date("1977-12-14");
      }
    }');
    Assert::equals(new Date('1977-12-14'), $r);
  }

  #[Test]
  public function resolves_alias() {
    $r= $this->run('namespace test; use util\Date as DateTime; class <T> {
      public function run() {
        return new DateTime("1977-12-14");
      }
    }');
    Assert::equals(new Date('1977-12-14'), $r);
  }

  #[Test]
  public function resolves_namespace_keyword() {
    $r= $this->run('namespace util; class <T> {
      public function run() {
        return new namespace\Date("1977-12-14");
      }
    }');
    Assert::equals(new Date('1977-12-14'), $r);
  }

  #[Test]
  public function resolves_sub_namespace() {
    $r= $this->run('class <T> {
      public function run() {
        return new namespace\util\Date("1977-12-14");
      }
    }');
    Assert::equals(new Date('1977-12-14'), $r);
  }

  #[Test]
  public function unqualified_namespace_constant() {
    $r= $this->run('namespace test\api; class <T> {
      public function run() {
        return v1::namespace;
      }
    }');
    Assert::equals('test\\api\\v1', $r);
  }

  #[Test]
  public function relative_namespace_constant() {
    $r= $this->run('namespace test; class <T> {
      public function run() {
        return api\v1::namespace;
      }
    }');
    Assert::equals('test\\api\\v1', $r);
  }

  #[Test]
  public function absolute_namespace_constant() {
    $r= $this->run('class <T> {
      public function run() {
        return \test\api\v1::namespace;
      }
    }');
    Assert::equals('test\\api\\v1', $r);
  }
}