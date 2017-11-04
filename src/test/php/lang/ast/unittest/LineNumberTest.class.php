<?php namespace lang\ast\unittest;

use text\StringTokenizer;
use lang\ast\Tokens;

class LineNumberTest extends \unittest\TestCase {

  /**
   * Assertion helper
   *
   * @param  [:var][] $expected
   * @param  lang.ast.Tokens $tokens
   * @throws unittest.AssertionFailedError
   * @return void
   */
  private function assertPositions($expected, $tokens) {
    $actual= [];
    foreach ($tokens as $type => $value) {
      $actual[]= [$value[0] => $value[1]];
    }
    $this->assertEquals($expected, $actual);
  }

  #[@test]
  public function starts_with_line_number_one() {
    $this->assertPositions(
      [['HERE' => 1]],
      new Tokens(new StringTokenizer("HERE"))
    );
  }

  #[@test]
  public function unix_lines() {
    $this->assertPositions(
      [['LINE1' => 1], ['LINE2' => 2]],
      new Tokens(new StringTokenizer("LINE1\nLINE2"))
    );
  }

  #[@test]
  public function windows_lines() {
    $this->assertPositions(
      [['LINE1' => 1], ['LINE2' => 2]],
      new Tokens(new StringTokenizer("LINE1\r\nLINE2"))
    );
  }

  #[@test]
  public function after_regular_comment() {
    $this->assertPositions(
      [['HERE' => 2]],
      new Tokens(new StringTokenizer("// Comment\nHERE"))
    );
  }

  #[@test]
  public function apidoc_comment() {
    $this->assertPositions(
      [['COMMENT' => 1], ['HERE' => 2]],
      new Tokens(new StringTokenizer("/** COMMENT */\nHERE"))
    );
  }

  #[@test]
  public function multi_line_apidoc_comment() {
    $this->assertPositions(
      [["COMMENT\n" => 1], ['HERE' => 3]],
      new Tokens(new StringTokenizer("/** COMMENT\n */\nHERE"))
    );
  }

  #[@test]
  public function multi_line_string() {
    $this->assertPositions(
      [["'STRING\n'" => 1], ['HERE' => 3]],
      new Tokens(new StringTokenizer("'STRING\n'\nHERE"))
    );
  }
}