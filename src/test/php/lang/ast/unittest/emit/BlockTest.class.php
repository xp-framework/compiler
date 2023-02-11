<?php namespace lang\ast\unittest\emit;

use test\{Assert, Test};

class BlockTest extends EmittingTest {

  #[Test]
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

  #[Test]
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