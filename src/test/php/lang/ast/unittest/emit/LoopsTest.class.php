<?php namespace lang\ast\unittest\emit;

use test\{Assert, Test};

class LoopsTest extends EmittingTest {

  #[Test]
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

  #[Test]
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

  #[Test]
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

  #[Test]
  public function foreach_with_destructuring() {
    $r= $this->run('class <T> {
      public function run() {
        $result= "";
        foreach ([[1, 2], [3, 4]] as [$a, $b]) $result.= ",".$a." & ".$b;
        return substr($result, 1);
      }
    }');

    Assert::equals('1 & 2,3 & 4', $r);
  }

  #[Test]
  public function foreach_with_destructuring_and_missing_expressions() {
    $r= $this->run('class <T> {
      public function run() {
        $result= "";
        foreach ([[1, 2, 3], [4, 5, 6]] as [$a, , $b]) $result.= ",".$a." & ".$b;
        return substr($result, 1);
      }
    }');

    Assert::equals('1 & 3,4 & 6', $r);
  }

  #[Test]
  public function foreach_with_destructuring_keys() {
    $r= $this->run('class <T> {
      public function run() {
        $result= "";
        foreach ([["a" => 1, "b" => 2], ["a" => 3, "b" => 4]] as ["a" => $a, "b" => $b]) $result.= ",".$a." & ".$b;
        return substr($result, 1);
      }
    }');

    Assert::equals('1 & 2,3 & 4', $r);
  }

  #[Test]
  public function foreach_with_destructuring_references() {
    $r= $this->run('class <T> {
      private $list= [[1, 2], [3, 4]];

      public function run() {
        foreach ($this->list as [&$a, &$b]) {
          $a*= 3;
          $b*= 2;
        }
        return $this->list;
      }
    }');

    Assert::equals([[3, 4], [9, 8]], $r);
  }

  #[Test]
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

  #[Test]
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

  #[Test]
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

  #[Test]
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

  #[Test]
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