<?php namespace lang\ast\unittest\emit;

use io\streams\MemoryOutputStream;
use lang\ast\emit\Escaping;
use test\{Assert, Test};

class EscapingTest {

  #[Test]
  public function can_create() {
    new Escaping(new MemoryOutputStream(), []);
  }

  #[Test]
  public function escape_single_quote() {
    $out= new MemoryOutputStream();
    $fixture= new Escaping($out, ["'" => "\\'"]);
    $fixture->write("'Hello', he said");

    Assert::equals("\\'Hello\\', he said", $out->bytes());
  }

  #[Test]
  public function calls_underlying_flush() {
    $out= new class() extends MemoryOutputStream {
      public $flushed= false;

      public function flush() {
        $this->flushed= true;
        parent::flush();
      }
    };
    $fixture= new Escaping($out, []);
    $fixture->flush();

    Assert::true($out->flushed);
  }

  #[Test]
  public function calls_underlying_close() {
    $out= new class() extends MemoryOutputStream {
      public $closed= false;

      public function close() {
        $this->closed= true;
        parent::close();
      }
    };
    $fixture= new Escaping($out, []);
    $fixture->close();

    Assert::true($out->closed);
  }
}