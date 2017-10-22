<?php namespace lang\ast\unittest\emit;

class TernaryTest extends EmittingTest {

  #[@test, @values(map= [true => "OK", false => "Fail"])]
  public function ternary($value, $result) {
    $this->assertEquals($result, $this->run(
      'class <T> {
        public function run($value) {
          return $value ? "OK" : "Fail";
        }
      }',
      $value
    ));
  }

  #[@test, @values(map= ["OK" => "OK", null => "Fail"])]
  public function short_ternary($value, $result) {
    $this->assertEquals($result, $this->run(
      'class <T> {
        public function run($value) {
          return $value ?: "Fail";
        }
      }',
      $value
    ));
  }

  #[@test, @values([[['OK']], [[]]])]
  public function null_coalesce($value) {
    $this->assertEquals('OK', $this->run(
      'class <T> {
        public function run($value) {
          return $value[0] ?? "OK";
        }
      }',
      $value
    ));
  }
}