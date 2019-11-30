<?php namespace lang\ast\unittest\emit;

use unittest\Assert;

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

    Assert::true($r);
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

    Assert::true($r);
  }
}