<?php namespace lang\ast\unittest\parse;

use lang\ast\nodes\Literal;
use unittest\Assert;

class CommentTest extends ParseTest {

  #[@test]
  public function oneline_double_slash() {
    $this->assertParsed([new Literal('"test"', 3)], '
      // This is a comment
      "test";
    ');
  }

  #[@test]
  public function oneline_double_slash_at_end() {
    $this->assertParsed([new Literal('"test"', 2)], '
      "test";  // This is a comment
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
  public function oneline_hashtag_at_end() {
    $this->assertParsed([new Literal('"test"', 2)], '
      "test";  # This is a comment
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
  public function oneline_slash_asterisk_at_end() {
    $this->assertParsed([new Literal('"test"', 2)], '
      "test";  /* This is a comment */
    ');
  }

  #[@test]
  public function oneline_slash_asterisk_inbetween() {
    $this->assertParsed([new Literal('"before"', 2), new Literal('"after"', 2)], '
      "before"; /* This is a comment */ "after";
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