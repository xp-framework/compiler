<?php namespace lang\ast\unittest\cli;

use io\streams\MemoryOutputStream;
use unittest\{Assert, Test};
use xp\compiler\ToStream;

class ToStreamTest {

  #[Test]
  public function can_create() {
    new ToStream(new MemoryOutputStream());
  }

  #[Test]
  public function write_to_stream() {
    $class= '<?php class Test { }';

    $out= new MemoryOutputStream();
    $fixture= new ToStream($out);
    with ($fixture->target('Test.php'), function($out) use($class) {
      $out->write($class);
      $out->flush();
      $out->close();
    });
    $fixture->close();

    Assert::equals($class, $out->bytes());
  }
}