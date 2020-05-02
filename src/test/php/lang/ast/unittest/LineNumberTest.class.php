<?php namespace lang\ast\unittest;

use lang\ast\Tokens;
use unittest\Assert;

class LineNumberTest {

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
    Assert::equals($expected, $actual);
  }

  #[@test]
  public function starts_with_line_number_one() {
    $this->assertPositions(
      [['HERE' => 1]],
      new Tokens("HERE")
    );
  }

  #[@test]
  public function unix_lines() {
    $this->assertPositions(
      [['LINE1' => 1], ['LINE2' => 2]],
      new Tokens("LINE1\nLINE2")
    );
  }

  #[@test]
  public function windows_lines() {
    $this->assertPositions(
      [['LINE1' => 1], ['LINE2' => 2]],
      new Tokens("LINE1\r\nLINE2")
    );
  }

  #[@test]
  public function after_regular_comment() {
    $this->assertPositions(
      [['HERE' => 2]],
      new Tokens("// Comment\nHERE")
    );
  }

  #[@test]
  public function apidoc_comment() {
    $this->assertPositions(
      [['COMMENT' => 1], ['HERE' => 2]],
      new Tokens("/** COMMENT */\nHERE")
    );
  }

  #[@test]
  public function multi_line_apidoc_comment() {
    $this->assertPositions(
      [["LINE1\nLINE2" => 1], ['HERE' => 3]],
      new Tokens("/** LINE1\nLINE2 */\nHERE")
    );
  }

  #[@test]
  public function multi_line_apidoc_comment_is_trimmed() {
    $this->assertPositions(
      [['COMMENT' => 1], ['HERE' => 3]],
      new Tokens("/** COMMENT\n */\nHERE")
    );
  }

  #[@test]
  public function multi_line_apidoc_comment_leading_stars_removed() {
    $this->assertPositions(
      [["LINE1\nLINE2" => 1], ['HERE' => 3]],
      new Tokens("/** LINE1\n * LINE2 */\nHERE")
    );
  }

  #[@test]
  public function multi_line_string() {
    $this->assertPositions(
      [["'STRING\n'" => 1], ['HERE' => 3]],
      new Tokens("'STRING\n'\nHERE")
    );
  }
}