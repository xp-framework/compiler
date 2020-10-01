<?php namespace lang\ast\unittest\emit;

use unittest\{Assert, Test};

class GotoTest extends EmittingTest {

  #[Test]
  public function skip_forward() {
    $r= $this->run('class <T> {
      public function run() {
        goto skip;
        return false;
        skip: return true;
      }
    }');

    Assert::true($r);
  }

  #[Test]
  public function skip_backward() {
    $r= $this->run('class <T> {
      public function run() {
        $return= false;
        retry: if ($return) return true;
        
        $return= true;
        goto retry;
        return false;
      }
    }');

    Assert::true($r);
  }
}