<?php namespace lang\ast\unittest\emit;

class IsOperatorTest extends EmittingTest {

  #[@test]
  public function this_is_self() {
    $r= $this->run('class <T> {
      public function run() {
        return $this is self;
      }
    }');

    $this->assertTrue($r);
  }

  #[@test]
  public function new_self_is_static() {
    $r= $this->run('class <T> {
      public function run() {
        return new self() is static;
      }
    }');

    $this->assertTrue($r);
  }

  #[@test]
  public function is_qualified_type() {
    $r= $this->run('class <T> {
      public function run() {
        return new \util\Date() is \util\Date;
      }
    }');

    $this->assertTrue($r);
  }

  #[@test]
  public function is_imported_type() {
    $r= $this->run('use util\Date; class <T> {
      public function run() {
        return new Date() is Date;
      }
    }');

    $this->assertTrue($r);
  }

  #[@test]
  public function is_aliased_type() {
    $r= $this->run('use util\Date as D; class <T> {
      public function run() {
        return new D() is D;
      }
    }');

    $this->assertTrue($r);
  }

  #[@test]
  public function is_type_variable() {
    $r= $this->run('class <T> {
      public function run() {
        $type= self::class;
        return new self() is $type;
      }
    }');

    $this->assertTrue($r);
  }

  #[@test]
  public function is_primitive_type() {
    $r= $this->run('class <T> {
      public function run() {
        return [1 is int, true is bool, -6.1 is float, "test" is string];
      }
    }');

    $this->assertEquals([true, true, true, true], $r);
  }

  #[@test]
  public function is_nullable_type() {
    $r= $this->run('class <T> {
      public function run() {
        return [null is ?int, null is ?self];
      }
    }');

    $this->assertEquals([true, true], $r);
  }

  #[@test]
  public function is_array_pseudo_type() {
    $r= $this->run('class <T> {
      public function run() {
        return [[] is array, [1, 2, 3] is array, ["key" => "value"] is array, null is array];
      }
    }');

    $this->assertEquals([true, true, true, false], $r);
  }

  #[@test]
  public function is_object_pseudo_type() {
    $r= $this->run('class <T> {
      public function run() {
        return [$this is object, function() { } is object, null is object];
      }
    }');

    $this->assertEquals([true, true, false], $r);
  }

  #[@test]
  public function is_callable_pseudo_type() {
    $r= $this->run('class <T> {
      public function run() {
        return [function() { } is callable, [$this, "run"] is callable, null is callable];
      }
    }');

    $this->assertEquals([true, true, false], $r);
  }

  #[@test]
  public function is_native_iterable_type() {
    $r= $this->run('class <T> implements \IteratorAggregate {
      public function getIterator() {
        yield 1;
      }

      public function run() {
        return [[] is iterable, $this is iterable, null is iterable];
      }
    }');

    $this->assertEquals([true, true, false], $r);
  }

  #[@test]
  public function is_map_type() {
    $r= $this->run('class <T> {
      public function run() {
        return [["key" => "value"] is array<string, string>, null is array<string, string>];
      }
    }');

    $this->assertEquals([true, false], $r);
  }

  #[@test]
  public function is_array_type() {
    $r= $this->run('class <T> {
      public function run() {
        return [["key"] is array<string>, ["key"] is array<int>, null is array<string>];
      }
    }');

    $this->assertEquals([true, false, false], $r);
  }

  #[@test]
  public function is_union_type() {
    $r= $this->run('class <T> {
      public function run() {
        return [1 is int|string, "test" is int|string, null is int|string];
      }
    }');

    $this->assertEquals([true, true, false], $r);
  }

  #[@test]
  public function is_function_type() {
    $r= $this->run('class <T> {
      public function run() {
        return [function(int $a): int { } is function(int): int, null is function(int): int];
      }
    }');

    $this->assertEquals([true, false], $r);
  }

  #[@test]
  public function precedence() {
    $r= $this->run('class <T> {
      public function run() {
        $arg= "Test";
        return $arg is string ? sprintf("string <%s>", $arg) : typeof($arg)->literal();
      }
    }');

    $this->assertEquals('string <Test>', $r);
  }
}