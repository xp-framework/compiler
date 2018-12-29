<?php namespace lang\ast\unittest\emit;

use lang\IllegalArgumentException;

class ExceptionsTest extends EmittingTest {

  #[@test]
  public function catch_exception() {
    $t= $this->type('class <T> {
      public function run() {
        try {
          throw new \\lang\\IllegalArgumentException("test");
        } catch (\\lang\\IllegalArgumentException $expected) {
          return get_class($expected);
        }
      }
    }');

    $this->assertEquals(IllegalArgumentException::class, $t->newInstance()->run());
  }

  #[@test]
  public function line_number_matches() {
    $t= $this->type('class <T> {
      public function run() {
        try {
          throw new \\lang\\IllegalArgumentException("test");
        } catch (\\lang\\IllegalArgumentException $expected) {
          return $expected->getLine();
        }
      }
    }');

    $this->assertEquals(4, $t->newInstance()->run());
  }

  #[@test]
  public function catch_without_type() {
    $t= $this->type('class <T> {
      public function run() {
        try {
          throw new \\lang\\IllegalArgumentException("test");
        } catch ($e) {
          return get_class($e);
        }
      }
    }');

    $this->assertEquals(IllegalArgumentException::class, $t->newInstance()->run());
  }

  #[@test]
  public function finally_without_exception() {
    $t= $this->type('class <T> {
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
    $this->assertTrue($instance->closed);
  }

  #[@test]
  public function finally_with_exception() {
    $t= $this->type('class <T> {
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
      $this->assertTrue($instance->closed);
    }
  }

  #[@test, @expect(IllegalArgumentException::class)]
  public function throw_expression_with_null_coalesce() {
    $t= $this->type('class <T> {
      public function run($user) {
        return $user ?? throw new \\lang\\IllegalArgumentException("test");
      }
    }');
    $t->newInstance()->run(null);
  }

  #[@test, @expect(IllegalArgumentException::class)]
  public function throw_expression_with_ternary() {
    $t= $this->type('class <T> {
      public function run($user) {
        return $user ?: throw new \\lang\\IllegalArgumentException("test");
      }
    }');
    $t->newInstance()->run(null);
  }

  #[@test, @expect(IllegalArgumentException::class)]
  public function throw_expression_with_short_ternary() {
    $t= $this->type('class <T> {
      public function run($user) {
        return $user ? new User($user) : throw new \\lang\\IllegalArgumentException("test");
      }
    }');
    $t->newInstance()->run(null);
  }

  #[@test, @expect(IllegalArgumentException::class)]
  public function throw_expression_with_lambda() {
    $t= $this->type('class <T> {
      public function run() {
        $f= () ==> throw new \\lang\\IllegalArgumentException("test");
        $f();
      }
    }');
    $t->newInstance()->run();
  }

  #[@test, @expect(IllegalArgumentException::class)]
  public function throw_expression_with_lambda_throwing_variable() {
    $t= $this->type('class <T> {
      public function run($e) {
        $f= () ==> throw $e;
        $f();
      }
    }');
    $t->newInstance()->run(new IllegalArgumentException('Test'));
  }

  #[@test, @expect(IllegalArgumentException::class)]
  public function throw_expression_with_lambda_capturing_variable() {
    $t= $this->type('class <T> {
      public function run() {
        $f= $message ==> throw new \\lang\\IllegalArgumentException($message);
        $f("test");
      }
    }');
    $t->newInstance()->run();
  }

  #[@test, @expect(IllegalArgumentException::class)]
  public function throw_expression_with_lambda_capturing_parameter() {
    $t= $this->type('class <T> {
      public function run($message) {
        $f= () ==> throw new \\lang\\IllegalArgumentException($message);
        $f();
      }
    }');
    $t->newInstance()->run('Test');
  }

  #[@test, @expect(IllegalArgumentException::class)]
  public function throw_expression_with_compact_method() {
    $t= $this->type('class <T> {
      public function run() ==> throw new \\lang\\IllegalArgumentException("test");
    }');
    $t->newInstance()->run();
  }
}