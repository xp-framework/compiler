<?php namespace lang\ast\unittest\emit;

use unittest\Assert;

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

    Assert::equals('1,2,3', $r);
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

    Assert::equals('a=1,b=2,c=3', $r);
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

    Assert::equals('1,2,3', $r);
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

    Assert::equals('1,2,3', $r);
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

    Assert::equals('1,2,3,4', $r);
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

    Assert::equals('1,2,3,4', $r);
  }

  #[@test]
  public function break_while_loop() {
    $r= $this->run('class <T> {
      public function run() {
        $i= 0;
        $r= [];
        while ($i++ < 5) {
          if (4 === $i) {
            break;
          } else {
            $r[]= $i;
          }
        }
        return $r;
      }
    }');

    Assert::equals([1, 2, 3], $r);
  }

  #[@test]
  public function continue_while_loop() {
    $r= $this->run('class <T> {
      public function run() {
        $i= 0;
        $r= [];
        while (++$i < 5) {
          if (1 === $i) {
            continue;
          } else {
            $r[]= $i;
          }
        }
        return $r;
      }
    }');

    Assert::equals([2, 3, 4], $r);
  }
}