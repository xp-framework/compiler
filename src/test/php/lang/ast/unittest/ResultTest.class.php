<?php namespace lang\ast\unittest;

use io\streams\MemoryOutputStream;
use lang\ast\Result;
use lang\ast\emit\{Declaration, Escaping, Reflection};
use lang\ast\nodes\ClassDeclaration;
use lang\{Value, ClassNotFoundException};
use unittest\{Assert, Expect, Test};

class ResultTest {

  #[Test]
  public function can_create() {
    new Result(new MemoryOutputStream());
  }

  #[Test]
  public function write() {
    $out= new MemoryOutputStream();
    $r= new Result($out);
    $r->out->write('echo "Hello";');
    Assert::equals('echo "Hello";', $out->bytes());
  }

  #[Test]
  public function write_escaped() {
    $out= new MemoryOutputStream();
    $r= new Result($out);

    $r->out->write("'");
    $r->out= new Escaping($out, ["'" => "\\'"]);
    $r->out->write("echo 'Hello'");

    $r->out= $out;
    $r->out->write("'");

    Assert::equals("'echo \'Hello\''", $out->bytes());
  }

  #[Test]
  public function line_number_initially_1() {
    $r= new Result(new MemoryOutputStream());
    Assert::equals(1, $r->line);
  }

  #[Test, Values([[1, 'test'], [2, "\ntest"], [3, "\n\ntest"]])]
  public function write_at_line($line, $expected) {
    $out= new MemoryOutputStream();
    $r= new Result($out);
    $r->at($line)->out->write('test');

    Assert::equals($expected, $out->bytes());
    Assert::equals($line, $r->line);
  }

  #[Test]
  public function at_cannot_go_backwards() {
    $out= new MemoryOutputStream();
    $r= new Result($out);
    $r->at(0)->out->write('test');

    Assert::equals('test', $out->bytes());
    Assert::equals(1, $r->line);
  }
}