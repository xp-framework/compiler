<?php namespace lang\ast\unittest\emit;

use lang\IllegalStateException;
use unittest\{Assert, Test, Values};

class ArraysTest extends EmittingTest {

  #[Test]
  public function array_literal() {
    $r= $this->run('class <T> {
      public function run() {
        return [1, 2, 3];
      }
    }');

    Assert::equals([1, 2, 3], $r);
  }

  #[Test]
  public function map_literal() {
    $r= $this->run('class <T> {
      public function run() {
        return ["a" => 1, "b" => 2];
      }
    }');

    Assert::equals(['a' => 1, 'b' => 2], $r);
  }

  #[Test]
  public function append() {
    $r= $this->run('class <T> {
      public function run() {
        $r= [1, 2];
        $r[]= 3;
        return $r;
      }
    }');

    Assert::equals([1, 2, 3], $r);
  }

  #[Test, Values(['[1, , 3]', '[1, , ]', '[, 1]']), Expect(class: IllegalStateException::class, withMessage: 'Cannot use empty array elements in arrays')]
  public function arrays_cannot_have_empty_elements($input) {
    $r= $this->run('class <T> {
      public function run() {
        return '.$input.';
      }
    }');
  }

  #[Test]
  public function destructuring() {
    $r= $this->run('class <T> {
      public function run() {
        [$a, $b]= [1, 2];
        return [$a, $b];
      }
    }');

    Assert::equals([1, 2], $r);
  }

  #[Test]
  public function destructuring_with_empty_between() {
    $r= $this->run('class <T> {
      public function run() {
        [$a, , $b]= [1, 2, 3];
        return [$a, $b];
      }
    }');

    Assert::equals([1, 3], $r);
  }

  #[Test]
  public function destructuring_with_empty_start() {
    $r= $this->run('class <T> {
      public function run() {
        [, $a, $b]= [1, 2, 3];
        return [$a, $b];
      }
    }');

    Assert::equals([2, 3], $r);
  }

  #[Test]
  public function destructuring_map() {
    $r= $this->run('class <T> {
      public function run() {
        ["two" => $a, "one" => $b]= ["one" => 1, "two" => 2];
        return [$a, $b];
      }
    }');

    Assert::equals([2, 1], $r);
  }

  #[Test]
  public function destructuring_map_with_expression() {
    $r= $this->run('class <T> {
      private $one= "one";
      public function run() {
        $two= "two";
        [$two => $a, $this->one => $b]= ["one" => 1, "two" => 2];
        return [$a, $b];
      }
    }');

    Assert::equals([2, 1], $r);
  }

  #[Test, Values(['$list', '$this->instance', 'self::$static'])]
  public function reference_destructuring($reference) {
    $r= $this->run('class <T> {
      private $instance= [1, 2];
      private static $static= [1, 2];

      public function run() {
        $list= [1, 2];
        [&$a, &$b]= '.$reference.';
        $a++;
        $b--;
        return '.$reference.';
      }
    }');

    Assert::equals([2, 1], $r);
  }

  #[Test]
  public function list_destructuring() {
    $r= $this->run('class <T> {
      public function run() {
        list($a, $b)= [1, 2];
        return [$a, $b];
      }
    }');

    Assert::equals([1, 2], $r);
  }

  #[Test]
  public function swap_using_destructuring() {
    $r= $this->run('class <T> {
      public function run() {
        $a= 1;
        $b= 2;
        [$b, $a]= [$a, $b];
        return [$a, $b];
      }
    }');

    Assert::equals([2, 1], $r);
  }

  #[Test]
  public function result_of_destructuring() {
    $r= $this->run('class <T> {
      public function run() {
        return [$a, $b]= [1, 2];
      }
    }');

    Assert::equals([1, 2], $r);
  }

  #[Test, Values([null, true, false, 0, 0.5, '', 'Test'])]
  public function destructuring_with_non_array($value) {
    $r= $this->run('class <T> {
      public function run($arg) {
        $r= [$a, $b]= $arg;
        return [$a, $b, $r];
      }
    }', $value);

    Assert::equals([null, null, $value], $r);
  }

  #[Test, Values([['key=value', ['key', 'value']], ['key', ['key', null]]])]
  public function destructuring_coalesce($input, $expected) {
    $r= $this->run('class <T> {
      public function run($input) {
        [$a, $b ?? null]= explode("=", $input, 2);
        return [$a, $b];
      }
    }', $input);

    Assert::equals($expected, $r);
  }

  #[Test, Values([[['a' => 1, 'b' => 2], [1, 2]], [['a' => 1], [1, null]]])]
  public function destructuring_coalesce_with_keys($input, $expected) {
    $r= $this->run('class <T> {
      public function run($input) {
        ["a" => $a, "b" => $b ?? null]= $input;
        return [$a, $b];
      }
    }', $input);

    Assert::equals($expected, $r);
  }

  #[Test]
  public function init_with_variable() {
    $r= $this->run('class <T> {
      public function run() {
        $KEY= "key";
        return [$KEY => "value"];
      }
    }');

    Assert::equals(['key' => 'value'], $r);
  }

  #[Test]
  public function init_with_member_variable() {
    $r= $this->run('class <T> {
      private static $KEY= "key";
      public function run() {
        return [self::$KEY => "value"];
      }
    }');

    Assert::equals(['key' => 'value'], $r);
  }
}