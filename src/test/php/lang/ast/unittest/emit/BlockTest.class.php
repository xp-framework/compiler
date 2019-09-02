<?php namespace lang\ast\unittest\emit;

class BlockTest extends EmittingTest {

  #[@test]
  public function empty_block() {
    $r= $this->run('class <T> {
      public function run() {
        {
        }
        return true;
      }
    }');

    $this->assertTrue($r);
  }

  #[@test]
  public function block_with_assignment() {
    $r= $this->run('class <T> {
      public function run() {
        {
          $result= true;
        }
        return $result;
      }
    }');

    $this->assertTrue($r);
  }
}