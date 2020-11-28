<?php namespace lang\ast\unittest;

use io\streams\{StringWriter, MemoryOutputStream};
use lang\ast\Result;
use unittest\{Assert, Test};

class ResultTest {

  #[Test]
  public function can_create() {
    new Result(new StringWriter(new MemoryOutputStream()));
  }

  #[Test]
  public function creates_unique_temporary_variables() {
    $r= new Result(new StringWriter(new MemoryOutputStream()));
    Assert::equals(['$_0', '$_1', '$_2'], [$r->temp(), $r->temp(), $r->temp()]);
  }

  #[Test]
  public function writes_php_open_tag_as_default_preamble() {
    $out= new MemoryOutputStream();
    $r= new Result(new StringWriter($out));
    Assert::equals('<?php ', $out->bytes());
  }

  #[Test, Values(['', '<?php '])]
  public function writes_preamble($preamble) {
    $out= new MemoryOutputStream();
    $r= new Result(new StringWriter($out), $preamble);
    Assert::equals($preamble, $out->bytes());
  }

  #[Test]
  public function write() {
    $out= new MemoryOutputStream();
    $r= new Result(new StringWriter($out));
    $r->out->write('echo "Hello";');
    Assert::equals('<?php echo "Hello";', $out->bytes());
  }
}