<?php namespace lang\ast\unittest\emit;

use lang\ast\Errors;
use lang\ast\nodes\{ClosureExpression, LambdaExpression};
use unittest\actions\VerifyThat;
use unittest\{Action, Assert, Test};

/**
 * Lambdas (a.k.a. arrow functions) support
 *
 * @see  https://wiki.php.net/rfc/arrow_functions_v2
 * @see  https://wiki.php.net/rfc/auto-capture-closure
 */
class LambdasTest extends EmittingTest {

  #[Test]
  public function inc() {
    $r= $this->run('class <T> {
      public function run() {
        return fn($a) => $a + 1;
      }
    }');

    Assert::equals(2, $r(1));
  }

  #[Test]
  public function add() {
    $r= $this->run('class <T> {
      public function run() {
        return fn($a, $b) => $a + $b;
      }
    }');

    Assert::equals(3, $r(1, 2));
  }

  #[Test]
  public function captures_this() {
    $r= $this->run('class <T> {
      private $addend= 2;

      public function run() {
        return fn($a) => $a + $this->addend;
      }
    }');

    Assert::equals(3, $r(1));
  }

  #[Test, Action(eval: 'new VerifyThat(fn() => property_exists(LambdaExpression::class, "static"))')]
  public function static_fn_does_not_capture_this() {
    $r= $this->run('class <T> {
      public function run() {
        return static fn() => isset($this);
      }
    }');

    Assert::false($r());
  }

  #[Test, Action(eval: 'new VerifyThat(fn() => property_exists(ClosureExpression::class, "static"))')]
  public function static_function_does_not_capture_this() {
    $r= $this->run('class <T> {
      public function run() {
        return static function() { return isset($this); };
      }
    }');

    Assert::false($r());
  }

  #[Test]
  public function captures_local() {
    $r= $this->run('class <T> {
      public function run() {
        $addend= 2;
        return fn($a) => $a + $addend;
      }
    }');

    Assert::equals(3, $r(1));
  }

  #[Test]
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

    Assert::equals(3, $r(1));
  }

  #[Test]
  public function captures_local_from_lambda() {
    $r= $this->run('class <T> {
      public function run() {
        $addend= 2;
        $f= fn() => fn($a) => $a + $addend;
        return $f();
      }
    }');

    Assert::equals(3, $r(1));
  }

  #[Test]
  public function captures_local_assigned_via_list() {
    $r= $this->run('class <T> {
      public function run() {
        [$addend]= [2];
        return fn($a) => $a + $addend;
      }
    }');

    Assert::equals(3, $r(1));
  }

  #[Test]
  public function captures_param() {
    $r= $this->run('class <T> {
      public function run($addend) {
        return fn($a) => $a + $addend;
      }
    }', 2);

    Assert::equals(3, $r(1));
  }

  #[Test]
  public function captures_braced_local() {
    $r= $this->run('class <T> {
      public function run() {
        $addend= 2;
        return fn($a) => $a + ($addend);
      }
    }');

    Assert::equals(3, $r(1));
  }

  #[Test]
  public function typed_parameters() {
    $r= $this->run('class <T> {
      public function run() {
        return fn(\\lang\\Value $in) => $in;
      }
    }');

    Assert::equals('lang.Value', typeof($r)->signature()[0]->getName());
  }

  #[Test]
  public function typed_return() {
    $r= $this->run('class <T> {
      public function run() {
        return fn($in): \\lang\\Value => $in;
      }
    }');

    Assert::equals('lang.Value', typeof($r)->returns()->getName());
  }

  #[Test]
  public function without_params() {
    $r= $this->run('class <T> {
      public function run() {
        return fn() => 1;
      }
    }');

    Assert::equals(1, $r());
  }

  #[Test]
  public function immediately_invoked_function_expression() {
    $r= $this->run('class <T> {
      public function run() {
        return (fn() => "IIFE")();
      }
    }');

    Assert::equals('IIFE', $r);
  }

  #[Test]
  public function with_block() {
    $r= $this->run('class <T> {
      public function run() {
        return fn() => {
          $a= 1;
          return $a + 1;
        };
      }
    }');

    Assert::equals(2, $r());
  }

  #[Test]
  public function capturing_with_block() {
    $r= $this->run('class <T> {
      public function run() {
        $a= 1;
        return fn() => {
          return $a + 1;
        };
      }
    }');

    Assert::equals(2, $r());
  }

  #[Test, Expect(Errors::class)]
  public function no_longer_supports_hacklang_variant() {
    $this->run('class <T> {
      public function run() {
        $func= ($arg) ==> { return 1; };
      }
    }');
  }
}