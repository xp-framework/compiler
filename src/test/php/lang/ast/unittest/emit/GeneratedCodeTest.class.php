<?php namespace lang\ast\unittest\emit;

use io\streams\MemoryOutputStream;
use lang\ast\emit\GeneratedCode;
use lang\{ClassNotFoundException, Value};
use test\{Assert, Expect, Test, Values};

class GeneratedCodeTest {

  #[Test]
  public function can_create() {
    new GeneratedCode(new MemoryOutputStream());
  }

  #[Test]
  public function creates_unique_temporary_variables() {
    $r= new GeneratedCode(new MemoryOutputStream());
    Assert::equals(['$_0', '$_1', '$_2'], [$r->temp(), $r->temp(), $r->temp()]);
  }

  #[Test]
  public function prolog_and_epilog_default_to_emtpy_strings() {
    $out= new MemoryOutputStream();
    $r= new GeneratedCode($out);
    Assert::equals('', $out->bytes());
  }

  #[Test, Values(['', '<?php '])]
  public function writes_prolog($prolog) {
    $out= new MemoryOutputStream();
    $r= new GeneratedCode($out, $prolog);
    $r->close();
    Assert::equals($prolog, $out->bytes());
  }

  #[Test]
  public function writes_epilog_on_closing() {
    $out= new MemoryOutputStream();
    $r= new GeneratedCode($out, '<?php ', '?>');
    $r->close();

    Assert::equals('<?php ?>', $out->bytes());
  }

  #[Test]
  public function line_number_initially_1() {
    $r= new GeneratedCode(new MemoryOutputStream());
    Assert::equals(1, $r->line);
  }

  #[Test, Values([[1, 'test'], [2, "\ntest"], [3, "\n\ntest"]])]
  public function write_at_line($line, $expected) {
    $out= new MemoryOutputStream();
    $r= new GeneratedCode($out);
    $r->at($line)->out->write('test');

    Assert::equals($expected, $out->bytes());
    Assert::equals($line, $r->line);
  }

  #[Test]
  public function at_cannot_go_backwards() {
    $out= new MemoryOutputStream();
    $r= new GeneratedCode($out);
    $r->at(0)->out->write('test');

    Assert::equals('test', $out->bytes());
    Assert::equals(1, $r->line);
  }
}