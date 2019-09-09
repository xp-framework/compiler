<?php namespace lang\ast\unittest\emit;

class EchoTest extends EmittingTest {

  /**
   * Runs statement and verifies a given expected value was echoed.
   *
   * @param  string $expected
   * @param  string $statement
   * @return void
   * @throws unittest.AssertionFailedError
   */
  private function assertEchoes($expected, $statement) {
    ob_start();
    try {
      $this->run('class <T> {
        private function hello() { return "Hello"; }
        public function run() { '.$statement.' }
      }');
      $this->assertEquals($expected, ob_get_contents());
    } finally {
      ob_end_clean();
    }
  }

  #[@test]
  public function echo_literal() {
    $this->assertEchoes('Hello', 'echo "Hello";');
  }

  #[@test]
  public function echo_variable() {
    $this->assertEchoes('Hello', '$a= "Hello"; echo $a;');
  }

  #[@test]
  public function echo_call() {
    $this->assertEchoes('Hello', 'echo $this->hello();');
  }

  #[@test]
  public function echo_with_multiple_arguments() {
    $this->assertEchoes('Hello World', 'echo "Hello", " ", "World";');
  }
}