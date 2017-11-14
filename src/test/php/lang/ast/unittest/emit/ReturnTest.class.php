<?php namespace lang\ast\unittest\emit;

class ReturnTest extends EmittingTest {

  #[@test]
  public function return_literal() {
    $r= $this->run('class <T> {
      public function run() {
        return "Test";
      }
    }');
    $this->assertEquals('Test', $r);
  }

  #[@test]
  public function return_member() {
    $r= $this->run('class <T> {
      private $member= "Test";

      public function run() {
        return $this->member;
      }
    }');
    $this->assertEquals('Test', $r);
  }

  #[@test]
  public function return_without_expression() {
    $r= $this->run('class <T> {
      public function run() {
        return;
      }
    }');
    $this->assertEquals(null, $r);
  }
}