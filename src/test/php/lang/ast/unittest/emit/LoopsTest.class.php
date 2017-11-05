<?php namespace lang\ast\unittest\emit;

class LoopsTest extends EmittingTest {

  #[@test]
  public function foreach_with_value() {
    $r= $this->run('class <T> {
      public function run() {
        $result= "";
        foreach ([1, 2, 3] as $number) {
          $result.= ",".$number;
        }
        return substr($result, 1);
      }
    }');

    $this->assertEquals('1,2,3', $r);
  }

  #[@test]
  public function foreach_with_key_and_value() {
    $r= $this->run('class <T> {
      public function run() {
        $result= "";
        foreach (["a" => 1, "b" => 2, "c" => 3] as $key => $number) {
          $result.= ",".$key."=".$number;
        }
        return substr($result, 1);
      }
    }');

    $this->assertEquals('a=1,b=2,c=3', $r);
  }

  #[@test]
  public function foreach_with_single_expression() {
    $r= $this->run('class <T> {
      public function run() {
        $result= "";
        foreach ([1, 2, 3] as $number) $result.= ",".$number;
        return substr($result, 1);
      }
    }');

    $this->assertEquals('1,2,3', $r);
  }

  #[@test]
  public function for_loop() {
    $r= $this->run('class <T> {
      public function run() {
        $result= "";
        for ($i= 1; $i < 4; $i++) {
          $result.= ",".$i;
        }
        return substr($result, 1);
      }
    }');

    $this->assertEquals('1,2,3', $r);
  }

  #[@test]
  public function while_loop() {
    $r= $this->run('class <T> {
      public function run() {
        $result= "";
        $i= 0;
        while ($i++ < 4) {
          $result.= ",".$i;
        }
        return substr($result, 1);
      }
    }');

    $this->assertEquals('1,2,3', $r);
  }

  #[@test]
  public function do_loop() {
    $r= $this->run('class <T> {
      public function run() {
        $result= "";
        $i= 1;
        do {
          $result.= ",".$i;
        } while ($i++ < 4);
        return substr($result, 1);
      }
    }');

    $this->assertEquals('1,2,3', $r);
  }
}