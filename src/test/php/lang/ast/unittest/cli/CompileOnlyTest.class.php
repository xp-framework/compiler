<?php namespace lang\ast\unittest\cli;

use test\Test;
use xp\compiler\CompileOnly;

class CompileOnlyTest {

  #[Test]
  public function can_create() {
    new CompileOnly();
  }

  #[Test]
  public function write_to_stream() {
    $fixture= new CompileOnly();
    with ($fixture->target('Test.php'), function($out) {
      $out->write('<?php class Test { }');
      $out->flush();
      $out->close();
    });
    $fixture->close();
  }
}