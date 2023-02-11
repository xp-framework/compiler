<?php namespace lang\ast\unittest\emit;

use test\{Assert, Test};

class InstanceOfTest extends EmittingTest {

  #[Test]
  public function this_is_instanceof_self() {
    $r= $this->run('class <T> {
      public function run() {
        return $this instanceof self;
      }
    }');

    Assert::true($r);
  }

  #[Test]
  public function new_self_is_instanceof_this() {
    $r= $this->run('class <T> {
      public function run() {
        return new self() instanceof $this;
      }
    }');

    Assert::true($r);
  }

  #[Test]
  public function instanceof_qualified_type() {
    $r= $this->run('class <T> {
      public function run() {
        return new \util\Date() instanceof \util\Date;
      }
    }');

    Assert::true($r);
  }

  #[Test]
  public function instanceof_imported_type() {
    $r= $this->run('use util\Date; class <T> {
      public function run() {
        return new Date() instanceof Date;
      }
    }');

    Assert::true($r);
  }

  #[Test]
  public function instanceof_aliased_type() {
    $r= $this->run('use util\Date as D; class <T> {
      public function run() {
        return new D() instanceof D;
      }
    }');

    Assert::true($r);
  }

  #[Test]
  public function instanceof_instance_expr() {
    $r= $this->run('class <T> {
      private $type= self::class;

      public function run() {
        return $this instanceof $this->type;
      }
    }');

    Assert::true($r);
  }

  #[Test]
  public function instanceof_scope_expr() {
    $r= $this->run('class <T> {
      private static $type= self::class;

      public function run() {
        return $this instanceof self::$type;
      }
    }');

    Assert::true($r);
  }

  #[Test]
  public function instanceof_expr() {
    $r= $this->run('class <T> {
      private function type() { return self::class; }

      public function run() {
        return $this instanceof ($this->type());
      }
    }');

    Assert::true($r);
  }
}