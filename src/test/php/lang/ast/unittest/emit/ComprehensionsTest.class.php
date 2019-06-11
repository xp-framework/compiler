<?php namespace lang\ast\unittest\emit;

class ComprehensionsTest extends EmittingTest {

  #[@test]
  public function for_expression() {
    $r= $this->run('class <T> {
      public function run() {
        return [for ($i= 1; $i < 4; $i++) yield $i];
      }
    }');

    $this->assertEquals([1, 2, 3], $r);
  }

  #[@test]
  public function for_expression_with_variables() {
    $r= $this->run('class <T> {
      public function run($factor) {
        return [for ($i= 1; $i < 4; $i++) yield $i * $factor];
      }
    }', 2);

    $this->assertEquals([2, 4, 6], $r);
  }

  #[@test]
  public function for_expression_with_if() {
    $r= $this->run('class <T> {
      public function run() {
        return [for ($i= 1; $i < 4; $i++) if (0 === $i % 2) yield $i];
      }
    }');

    $this->assertEquals([2], $r);
  }

  #[@test]
  public function nested_for_expression() {
    $r= $this->run('class <T> {
      public function run() {
        return [for ($i= 1; $i < 4; $i++) for ($j= 1; $j < 4; $j++) yield $i * $j];
      }
    }');

    $this->assertEquals([1, 2, 3, 2, 4, 6, 3, 6, 9], $r);
  }

  #[@test]
  public function foreach_expression() {
    $r= $this->run('class <T> {
      public function run() {
        $input= ["one" => "eins", "three" => "drei"];
        return [foreach ($input as $key => $val) yield $val => $key];
      }
    }');

    $this->assertEquals(['eins' => 'one', 'drei' => 'three'], $r);
  }

  #[@test]
  public function if_expression_true() {
    $r= $this->run('class <T> {
      public function run() {
        return [if (true) yield 1];
      }
    }');

    $this->assertEquals([1], $r);
  }

  #[@test]
  public function if_expression_false() {
    $r= $this->run('class <T> {
      public function run() {
        return [if (false) yield 1];
      }
    }');

    $this->assertEquals([], $r);
  }

  #[@test]
  public function if_expression_with_variables() {
    $r= $this->run('class <T> {
      public function run($conditional) {
        return [if (true) yield $conditional];
      }
    }', 2);

    $this->assertEquals([2], $r);
  }

  #[@test, @values(map= [
  #  true  => [1, 2, 3],
  #  false => [1, 3],
  #])]
  public function if_expression_with_array($condition, $expected) {
    $r= $this->run('class <T> {
      public function run($condition) {
        return [1, if ($condition) yield 2, 3];
      }
    }', $condition);

    $this->assertEquals($expected, $r);
  }
}