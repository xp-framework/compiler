<?php namespace lang\ast\unittest\emit;

use lang\{IllegalArgumentException, Error};
use test\{Assert, Expect, Test};

/**
 * Property hooks
 *
 * @see  https://wiki.php.net/rfc/property-hooks
 */
class PropertyHooksTest extends EmittingTest {

  #[Test]
  public function get_expression() {
    $r= $this->run('class <T> {
      public $test { get => "Test"; }

      public function run() {
        return $this->test;
      }
    }');

    Assert::equals('Test', $r);
  }

  #[Test]
  public function get_block() {
    $r= $this->run('class <T> {
      public $test { get { return "Test"; } }

      public function run() {
        return $this->test;
      }
    }');

    Assert::equals('Test', $r);
  }

  #[Test]
  public function abbreviated_get() {
    $r= $this->run('class <T> {
      private $word= "Test";
      private $interpunction= "!"; 

      public $test => $this->word.$this->interpunction;

      public function run() {
        return $this->test;
      }
    }');

    Assert::equals('Test!', $r);
  }

  #[Test]
  public function set_expression() {
    $r= $this->run('class <T> {
      public $test { set => $field= ucfirst($value); }

      public function run() {
        $this->test= "test";
        return $this->test;
      }
    }');

    Assert::equals('Test', $r);
  }

  #[Test]
  public function set_block() {
    $r= $this->run('class <T> {
      public $test { set($value) { $field= ucfirst($value); } }

      public function run() {
        $this->test= "test";
        return $this->test;
      }
    }');

    Assert::equals('Test', $r);
  }

  #[Test, Expect(IllegalArgumentException::class)]
  public function set_raising_exception() {
    $this->run('use lang\\IllegalArgumentException; class <T> {
      public $test { set($value) { throw new IllegalArgumentException("Cannot set"); } }

      public function run() {
        $this->test= "test";
      }
    }');
  }

  #[Test]
  public function get_and_set_using_field() {
    $r= $this->run('class <T> {
      public $test {
        get => $field;
        set => $field= ucfirst($value);
      }

      public function run() {
        $this->test= "test";
        return $this->test;
      }
    }');

    Assert::equals('Test', $r);
  }

  #[Test]
  public function implicit_set() {
    $r= $this->run('class <T> {
      public $test {
        get => ucfirst($field);
      }

      public function run() {
        $this->test= "test";
        return $this->test;
      }
    }');

    Assert::equals('Test', $r);
  }

  #[Test]
  public function typed_set() {
    $r= $this->run('use util\\Bytes; class <T> {
      public string $test {
        set(string|Bytes $arg) => $field= ucfirst($arg);
      }

      public function run() {
        $this->test= new Bytes(["t", "e", "s", "t"]);
        return $this->test;
      }
    }');

    Assert::equals('Test', $r);
  }

  #[Test, Expect(class: Error::class, message: '/Argument .+ type int(eger)?, string given/')]
  public function typed_mismatch() {
    $this->run('class <T> {
      public string $test {
        set(int $times) => $field= $times." times";
      }

      public function run() {
        $this->test= "no";
      }
    }');
  }

  #[Test]
  public function initial_value() {
    $r= $this->run('class <T> {
      public $test= "test" {
        get => ucfirst($field);
      }

      public function run() {
        return $this->test;
      }
    }');

    Assert::equals('Test', $r);
  }

  #[Test]
  public function reflection() {
    $t= $this->type('class <T> {
      public string $test {
        get => $field;
        set => $field= ucfirst($value);
      }
    }');

    Assert::equals('public string '.$t->getName().'::$test', $t->getField('test')->toString());
  }

  #[Test]
  public function property_constant() {
    $r= $this->run('class <T> {
      public $test { get => __PROPERTY__; }

      public function run() {
        return $this->test;
      }
    }');

    Assert::equals('test', $r);
  }

  #[Test]
  public function reflection_of_interface_fields() {
    $t= $this->type('interface <T> {
      public $test { get; }
    }');

    Assert::equals('public var '.$t->getName().'::$test', $t->getField('test')->toString());
  }

  #[Test]
  public function line_number_in_thrown_expression() {
    $r= $this->run('use lang\\IllegalArgumentException; class <T> {
      public $test {
        set(string $name) {
          if (strlen($name) > 10) throw new IllegalArgumentException("Too long");
          $field= $name;
        }
      }

      public function run() {
        try {
          $this->test= "this is too long";
          return null;
        } catch (IllegalArgumentException $expected) {
          return $expected->getLine();
        }
      }
    }');

    Assert::equals(4, $r);
  }
}