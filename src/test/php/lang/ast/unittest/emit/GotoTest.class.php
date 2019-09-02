<?php namespace lang\ast\unittest\emit;

class GotoTest extends EmittingTest {

  #[@test]
  public function skip_forward() {
    $r= $this->run('class <T> {
      public function run() {
        goto skip;
        return false;
        skip: return true;
      }
    }');

    $this->assertTrue($r);
  }

  #[@test]
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

    $this->assertTrue($r);
  }
}