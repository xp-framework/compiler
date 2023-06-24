<?php namespace lang\ast\unittest\emit;

use lang\IllegalArgumentException;
use test\{Assert, Expect, Test};

class ExceptionsTest extends EmittingTest {

  #[Test]
  public function catch_exception() {
    $t= $this->declare('class %T {
      public function run() {
        try {
          throw new \\lang\\IllegalArgumentException("test");
        } catch (\\lang\\IllegalArgumentException $expected) {
          return get_class($expected);
        }
      }
    }');

    Assert::equals(IllegalArgumentException::class, $t->newInstance()->run());
  }

  #[Test]
  public function line_number_matches() {
    $t= $this->declare('class %T {
      public function run() {
        try {
          throw new \\lang\\IllegalArgumentException("test");
        } catch (\\lang\\IllegalArgumentException $expected) {
          return $expected->getLine();
        }
      }
    }');

    Assert::equals(4, $t->newInstance()->run());
  }

  #[Test]
  public function catch_without_type() {
    $t= $this->declare('class %T {
      public function run() {
        try {
          throw new \\lang\\IllegalArgumentException("test");
        } catch ($e) {
          return get_class($e);
        }
      }
    }');

    Assert::equals(IllegalArgumentException::class, $t->newInstance()->run());
  }

  #[Test]
  public function non_capturing_catch() {
    $t= $this->declare('class %T {
      public function run() {
        try {
          throw new \\lang\\IllegalArgumentException("test");
        } catch (\\lang\\IllegalArgumentException) {
          return "Expected";
        }
      }
    }');

    Assert::equals('Expected', $t->newInstance()->run());
  }

  #[Test]
  public function finally_without_exception() {
    $t= $this->declare('class %T {
      public $closed= false;
      public function run() {
        try {
          // Nothing
        } finally {
          $this->closed= true;
        }
      }
    }');

    $instance= $t->newInstance();
    $instance->run();
    Assert::true($instance->closed);
  }

  #[Test]
  public function finally_with_exception() {
    $t= $this->declare('class %T {
      public $closed= false;
      public function run() {
        try {
          throw new \\lang\\IllegalArgumentException("test");
        } finally {
          $this->closed= true;
        }
      }
    }');

    $instance= $t->newInstance();
    try {
      $instance->run();
      $this->fail('Expected exception not caught', null, IllegalArgumentException::class);
    } catch (IllegalArgumentException $expected) {
      Assert::true($instance->closed);
    }
  }

  #[Test, Expect(IllegalArgumentException::class)]
  public function throw_expression_with_this() {
    $this->run('class %T {
      private $message= "test";
      public function run($user= null) {
        return $user ?? throw new \\lang\\IllegalArgumentException($this->message);
      }
    }');
  }

  #[Test, Expect(IllegalArgumentException::class)]
  public function throw_expression_with_null_coalesce() {
    $t= $this->declare('class %T {
      public function run($user) {
        return $user ?? throw new \\lang\\IllegalArgumentException("test");
      }
    }');
    $t->newInstance()->run(null);
  }

  #[Test, Expect(IllegalArgumentException::class)]
  public function throw_expression_with_short_ternary() {
    $t= $this->declare('class %T {
      public function run($user) {
        return $user ?: throw new \\lang\\IllegalArgumentException("test");
      }
    }');
    $t->newInstance()->run(null);
  }

  #[Test, Expect(IllegalArgumentException::class)]
  public function throw_expression_with_normal_ternary() {
    $t= $this->declare('class %T {
      public function run($user) {
        return $user ? new User($user) : throw new \\lang\\IllegalArgumentException("test");
      }
    }');
    $t->newInstance()->run(null);
  }

  #[Test, Expect(IllegalArgumentException::class)]
  public function throw_expression_with_binary_or() {
    $t= $this->declare('class %T {
      public function run($user) {
        return $user || throw new \\lang\\IllegalArgumentException("test");
      }
    }');
    $t->newInstance()->run(null);
  }

  #[Test, Expect(IllegalArgumentException::class)]
  public function throw_expression_with_binary_and() {
    $t= $this->declare('class %T {
      public function run($error) {
        return $error && throw new \\lang\\IllegalArgumentException("test");
      }
    }');
    $t->newInstance()->run(true);
  }

  #[Test, Expect(IllegalArgumentException::class)]
  public function throw_expression_with_lambda() {
    $this->run('use lang\IllegalArgumentException; class %T {
      public function run() {
        $f= fn() => throw new IllegalArgumentException("test");
        $f();
      }
    }');
  }

  #[Test, Expect(IllegalArgumentException::class)]
  public function throw_expression_with_lambda_throwing_variable() {
    $t= $this->declare('class %T {
      public function run($e) {
        $f= fn() => throw $e;
        $f();
      }
    }');
    $t->newInstance()->run(new IllegalArgumentException('Test'));
  }

  #[Test, Expect(IllegalArgumentException::class)]
  public function throw_expression_with_lambda_capturing_variable() {
    $this->run('use lang\IllegalArgumentException; class %T {
      public function run() {
        $f= fn($message) => throw new IllegalArgumentException($message);
        $f("test");
      }
    }');
  }

  #[Test, Expect(IllegalArgumentException::class)]
  public function throw_expression_with_lambda_capturing_parameter() {
    $t= $this->declare('use lang\IllegalArgumentException; class %T {
      public function run($message) {
        $f= fn() => throw new IllegalArgumentException($message);
        $f();
      }
    }');
    $t->newInstance()->run('Test');
  }

  #[Test]
  public function try_catch_nested_inside_catch() {
    $t= $this->declare('class %T {
      public function run() {
        try {
          throw new \\lang\\IllegalArgumentException("test");
        } catch (\\lang\\IllegalArgumentException $expected) {
          try {
            throw $expected;
          } catch (\\lang\\IllegalArgumentException $expected) {
            return $expected->getMessage();
          }
        }
      }
    }');

    Assert::equals('test', $t->newInstance()->run());
  }
}