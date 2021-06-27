<?php namespace lang\ast\unittest\emit;

use unittest\{Assert, Test};

/**
 * Partial Function Application
 *
 * @see  https://wiki.php.net/rfc/partial_function_application
 */
class PartialFunctionApplicationTest extends EmittingTest {

  #[Test]
  public function on_builtin() {
    $f= $this->run('class <T> {
      public function run() {
        return strlen(?);
      }
    }');
    Assert::equals(4, $f('test'));
  }

  #[Test]
  public function on_instance_method() {
    $f= $this->run('class <T> {
      private function length($str) { return strlen($str); }

      public function run() {
        return $this->length(?);
      }
    }');
    Assert::equals(4, $f('test'));
  }

  #[Test]
  public function on_class_method() {
    $f= $this->run('class <T> {
      private static function length($str) { return strlen($str); }

      public function run() {
        return self::length(?);
      }
    }');
    Assert::equals(4, $f('test'));
  }

  #[Test]
  public function on_constructor() {
    $f= $this->run('use lang\ast\unittest\emit\Handle; class <T> {
      public function run() {
        return new Handle(?);
      }
    }');
    Assert::equals(new Handle(0), $f(0));
  }

  #[Test]
  public function first_argument() {
    $f= $this->run('class <T> {
      public function run() {
        return strpos(?, ",");
      }
    }');
    Assert::equals(4, $f('test,works,'));
  }

  #[Test]
  public function last_argument() {
    $f= $this->run('class <T> {
      public function run() {
        return strpos("test,works,", ?);
      }
    }');
    Assert::equals(4, $f(','));
  }

  #[Test]
  public function middle_argument() {
    $f= $this->run('class <T> {
      public function run() {
        return strpos("test,works,", ?, 5);
      }
    }');
    Assert::equals(10, $f(','));
  }

  #[Test]
  public function variable_argument() {
    $f= $this->run('class <T> {
      public function run() {
        return strpos(...);
      }
    }');
    Assert::equals(4, $f('test,works,', ','));
  }

  #[Test]
  public function with_array_map() {
    $r= $this->run('use lang\ast\unittest\emit\Handle; class <T> {
      public function run() {
        return array_map(new Handle(?), [0, 1, 2]);
      }
    }');
    Assert::equals([new Handle(0), new Handle(1), new Handle(2)], $r);
  }

  #[Test, Values([0, 1, 2])]
  public function arguments_evaluated_once_when_closure_is_created($invocations) {
    $t= $this->type('class <T> {
      public $offset= 0;

      public function run() {
        return strpos(?, ",", $this->offset++);
      }
    }');

    $i= $t->newInstance();
    $f= $i->run();
    for ($j= 0; $j < $invocations; $j++) {
      $f((string)$j);
    }

    Assert::equals(1, $i->offset);
  }
}