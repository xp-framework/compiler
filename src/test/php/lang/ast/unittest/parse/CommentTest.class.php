<?php namespace lang\ast\unittest\parse;

use lang\ast\nodes\Literal;

class CommentTest extends ParseTest {

  #[@test]
  public function oneline_double_slash() {
    $this->assertParsed([new Literal('"test"', 3)], '
      // This is a comment
      "test";
    ');
  }

  #[@test]
  public function two_oneline_double_slash() {
    $this->assertParsed([new Literal('"test"', 4)], '
      // This is a comment
      // This is another
      "test";
    ');
  }

  #[@test]
  public function oneline_hashtag() {
    $this->assertParsed([new Literal('"test"', 3)], '
      # This is a comment
      "test";
    ');
  }

  #[@test]
  public function two_oneline_hashtags() {
    $this->assertParsed([new Literal('"test"', 4)], '
      # This is a comment
      # This is another
      "test";
    ');
  }

  #[@test]
  public function oneline_slash_asterisk() {
    $this->assertParsed([new Literal('"test"', 3)], '
      /* This is a comment */
      "test";
    ');
  }

  #[@test]
  public function multiline_slash_asterisk() {
    $this->assertParsed([new Literal('"test"', 5)], '
      /* This is a comment
       * spanning multiple lines.
       */
      "test";
    ');
  }
}