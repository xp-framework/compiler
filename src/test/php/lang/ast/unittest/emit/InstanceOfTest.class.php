<?php namespace lang\ast\unittest\emit;

class InstanceOfTest extends EmittingTest {

  #[@test]
  public function this_is_instanceof_self() {
    $r= $this->run('class <T> {
      public function run() {
        return $this instanceof self;
      }
    }');

    $this->assertTrue($r);
  }

  #[@test]
  public function new_self_is_instanceof_this() {
    $r= $this->run('class <T> {
      public function run() {
        return new self() instanceof $this;
      }
    }');

    $this->assertTrue($r);
  }

  #[@test]
  public function instanceof_qualified_type() {
    $r= $this->run('class <T> {
      public function run() {
        return new \util\Date() instanceof \util\Date;
      }
    }');

    $this->assertTrue($r);
  }

  #[@test]
  public function instanceof_imported_type() {
    $r= $this->run('use util\Date; class <T> {
      public function run() {
        return new Date() instanceof Date;
      }
    }');

    $this->assertTrue($r);
  }
}