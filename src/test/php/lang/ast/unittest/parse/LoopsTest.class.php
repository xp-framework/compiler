<?php namespace lang\ast\unittest\parse;

use lang\ast\nodes\{Assignment, BinaryExpression, BreakStatement, ContinueStatement, DoLoop, ForLoop, ForeachLoop, GotoStatement, InvokeExpression, Label, Literal, UnaryExpression, Variable, WhileLoop};
use unittest\Assert;

class LoopsTest extends ParseTest {
  private $loop;

  /** @return void */
  #[@before]
  public function setUp() {
    $this->loop= new InvokeExpression(new Literal('loop', self::LINE), [], self::LINE);
  }

  #[@test]
  public function foreach_value() {
    $this->assertParsed(
      [new ForeachLoop(
        new Variable('iterable', self::LINE),
        null,
        new Variable('value', self::LINE),
        [$this->loop],
        self::LINE
      )],
      'foreach ($iterable as $value) { loop(); }'
    );
  }

  #[@test]
  public function foreach_key_value() {
    $this->assertParsed(
      [new ForeachLoop(
        new Variable('iterable', self::LINE),
        new Variable('key', self::LINE),
        new Variable('value', self::LINE),
        [$this->loop],
        self::LINE
      )],
      'foreach ($iterable as $key => $value) { loop(); }'
    );
  }

  #[@test]
  public function foreach_value_without_curly_braces() {
    $this->assertParsed(
      [new ForeachLoop(
        new Variable('iterable', self::LINE),
        null,
        new Variable('value', self::LINE),
        [$this->loop],
        self::LINE
      )],
      'foreach ($iterable as $value) loop();'
    );
  }

  #[@test]
  public function for_loop() {
    $this->assertParsed(
      [new ForLoop(
        [new Assignment(new Variable('i', self::LINE), '=', new Literal('0', self::LINE), self::LINE)],
        [new BinaryExpression(new Variable('i', self::LINE), '<', new Literal('10', self::LINE), self::LINE)],
        [new UnaryExpression('suffix', new Variable('i', self::LINE), '++', self::LINE)],
        [$this->loop],
        self::LINE
      )],
      'for ($i= 0; $i < 10; $i++) { loop(); }'
    );
  }

  #[@test]
  public function while_loop() {
    $this->assertParsed(
      [new WhileLoop(
        new Variable('continue', self::LINE),
        [$this->loop],
        self::LINE
      )],
      'while ($continue) { loop(); }'
    );
  }

  #[@test]
  public function while_loop_without_curly_braces() {
    $this->assertParsed(
      [new WhileLoop(
        new Variable('continue', self::LINE),
        [$this->loop],
        self::LINE
      )],
      'while ($continue) loop();'
    );
  }

  #[@test]
  public function do_loop() {
    $this->assertParsed(
      [new DoLoop(
        new Variable('continue', self::LINE),
        [$this->loop],
        self::LINE
      )],
      'do { loop(); } while ($continue);'
    );
  }

  #[@test]
  public function do_loop_without_curly_braces() {
    $this->assertParsed(
      [new DoLoop(
        new Variable('continue', self::LINE),
        [$this->loop],
        self::LINE
      )],
      'do loop(); while ($continue);'
    );
  }

  #[@test]
  public function break_statement() {
    $this->assertParsed(
      [new BreakStatement(null, self::LINE)],
      'break;'
    );
  }

  #[@test]
  public function break_statement_with_level() {
    $this->assertParsed(
      [new BreakStatement(new Literal('2', self::LINE), self::LINE)],
      'break 2;'
    );
  }

  #[@test]
  public function continue_statement() {
    $this->assertParsed(
      [new ContinueStatement(null, self::LINE)],
      'continue;'
    );
  }

  #[@test]
  public function continue_statement_with_level() {
    $this->assertParsed(
      [new ContinueStatement(new Literal('2', self::LINE), self::LINE)],
      'continue 2;'
    );
  }

  #[@test]
  public function goto_statement() {
    $this->assertParsed(
      [new Label('start', self::LINE), $this->loop, new GotoStatement('start', self::LINE)],
      'start: loop(); goto start;'
    );
  }
}