<?php namespace lang\ast\unittest\emit;

use unittest\Assert;

class ReturnTest extends EmittingTest {

  #[@test]
  public function return_literal() {
    $r= $this->run('class <T> {
      public function run() {
        return "Test";
      }
    }');
    Assert::equals('Test', $r);
  }

  #[@test]
  public function return_member() {
    $r= $this->run('class <T> {
      private $member= "Test";

      public function run() {
        return $this->member;
      }
    }');
    Assert::equals('Test', $r);
  }

  #[@test]
  public function return_without_expression() {
    $r= $this->run('class <T> {
      public function run() {
        return;
      }
    }');
    Assert::equals(null, $r);
  }
}