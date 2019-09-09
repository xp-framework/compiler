<?php namespace lang\ast\unittest\emit;

use unittest\actions\RuntimeVersion;

/**
 * Lambdas (a.k.a. arrow functions) support
 *
 * @see  https://wiki.php.net/rfc/arrow_functions_v2
 */
class LambdasTest extends EmittingTest {

  #[@test]
  public function inc() {
    $r= $this->run('class <T> {
      public function run() {
        return fn($a) => $a + 1;
      }
    }');

    $this->assertEquals(2, $r(1));
  }

  #[@test]
  public function add() {
    $r= $this->run('class <T> {
      public function run() {
        return fn($a, $b) => $a + $b;
      }
    }');

    $this->assertEquals(3, $r(1, 2));
  }

  #[@test]
  public function captures_this() {
    $r= $this->run('class <T> {
      private $addend= 2;

      public function run() {
        return fn($a) => $a + $this->addend;
      }
    }');

    $this->assertEquals(3, $r(1));
  }

  #[@test]
  public function captures_local() {
    $r= $this->run('class <T> {
      public function run() {
        $addend= 2;
        return fn($a) => $a + $addend;
      }
    }');

    $this->assertEquals(3, $r(1));
  }

  #[@test]
  public function captures_local_from_use_list() {
    $r= $this->run('class <T> {
      public function run() {
        $addend= 2;
        $f= function() use($addend) {
          return fn($a) => $a + $addend;
        };
        return $f();
      }
    }');

    $this->assertEquals(3, $r(1));
  }

  #[@test]
  public function captures_local_from_lambda() {
    $r= $this->run('class <T> {
      public function run() {
        $addend= 2;
        $f= fn() => fn($a) => $a + $addend;
        return $f();
      }
    }');

    $this->assertEquals(3, $r(1));
  }

  #[@test]
  public function captures_local_assigned_via_list() {
    $r= $this->run('class <T> {
      public function run() {
        [$addend]= [2];
        return fn($a) => $a + $addend;
      }
    }');

    $this->assertEquals(3, $r(1));
  }

  #[@test]
  public function captures_param() {
    $r= $this->run('class <T> {
      public function run($addend) {
        return fn($a) => $a + $addend;
      }
    }', 2);

    $this->assertEquals(3, $r(1));
  }

  #[@test]
  public function captures_braced_local() {
    $r= $this->run('class <T> {
      public function run() {
        $addend= 2;
        return fn($a) => $a + ($addend);
      }
    }');

    $this->assertEquals(3, $r(1));
  }

  #[@test]
  public function typed_parameters() {
    $r= $this->run('class <T> {
      public function run() {
        return fn(\\lang\\Value $in) => $in;
      }
    }');

    $this->assertEquals('lang.Value', typeof($r)->signature()[0]->getName());
  }

  #[@test, @action(new RuntimeVersion('>=7.0'))]
  public function typed_return() {
    $r= $this->run('class <T> {
      public function run() {
        return fn($in): \\lang\\Value => $in;
      }
    }');

    $this->assertEquals('lang.Value', typeof($r)->returns()->getName());
  }

  #[@test]
  public function without_params() {
    $r= $this->run('class <T> {
      public function run() {
        return fn() => 1;
      }
    }');

    $this->assertEquals(1, $r());
  }

  #[@test]
  public function immediately_invoked_function_expression() {
    $r= $this->run('class <T> {
      public function run() {
        return (fn() => "IIFE")();
      }
    }');

    $this->assertEquals('IIFE', $r);
  }

  #[@test]
  public function with_block() {
    $r= $this->run('class <T> {
      public function run() {
        return fn() => {
          $a= 1;
          return $a + 1;
        };
      }
    }');

    $this->assertEquals(2, $r());
  }
}