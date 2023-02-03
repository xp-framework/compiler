<?php namespace lang\ast\unittest;

use io\streams\MemoryOutputStream;
use lang\ast\emit\{Declaration, Escaping, Reflection, Result};
use lang\ast\nodes\ClassDeclaration;
use lang\{ClassNotFoundException, Value};
use test\{Assert, Expect, Test};

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
}