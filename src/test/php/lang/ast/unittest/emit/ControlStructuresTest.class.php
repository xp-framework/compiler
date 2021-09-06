<?php namespace lang\ast\unittest\emit;

use lang\Throwable;
use unittest\{Assert, Expect, Test, Values};

class ControlStructuresTest extends EmittingTest {

  #[Test, Values([[0, 'no items'], [1, 'one item'], [2, '2 items'], [3, '3 items'],])]
  public function if_else_cascade($input, $expected) {
    $r= $this->run('class <T> {
      public function run($arg) {
        if (0 === $arg) {
          return "no items";
        } else if (1 === $arg) {
          return "one item";
        } else {
          return $arg." items";
        }
      }
    }', $input);

    Assert::equals($expected, $r);
  }

  #[Test, Values([[0, 'no items'], [1, 'one item'], [2, '2 items'], [3, '3 items'],])]
  public function switch_case($input, $expected) {
    $r= $this->run('class <T> {
      public function run($arg) {
        switch ($arg) {
          case 0: return "no items";
          case 1: return "one item";
          default: return $arg." items";
        }
      }
    }', $input);

    Assert::equals($expected, $r);
  }

  #[Test, Values([[SEEK_SET, 10], [SEEK_CUR, 11]])]
  public function switch_case_goto_label_ambiguity($whence, $expected) {
    $r= $this->run('class <T> {
      public function run($arg) {
        $position= 1;
        switch ($arg) {
          case SEEK_SET: $position= 10; break;
          case SEEK_CUR: $position+= 10; break;
        }
        return $position;
      }
    }', $whence);

    Assert::equals($expected, $r);
  }

  #[Test, Values([[SEEK_SET, 10], [SEEK_CUR, 11]])]
  public function switch_case_constant_ambiguity($whence, $expected) {
    $r= $this->run('class <T> {
      const SET = SEEK_SET;
      const CURRENT = SEEK_CUR;
      public function run($arg) {
        $position= 1;
        switch ($arg) {
          case self::SET: $position= 10; break;
          case self::CURRENT: $position+= 10; break;
        }
        return $position;
      }
    }', $whence);

    Assert::equals($expected, $r);
  }

  #[Test, Values([[0, 'no items'], [1, 'one item'], [2, '2 items'], [3, '3 items']])]
  public function match($input, $expected) {
    $r= $this->run('class <T> {
      public function run($arg) {
        return match ($arg) {
          0 => "no items",
          1 => "one item",
          default => $arg." items",
        };
      }
    }', $input);

    Assert::equals($expected, $r);
  }

  #[Test, Values([[200, 'OK'], [302, 'Redirect'], [404, 'Error #404']])]
  public function match_with_multiple_cases($input, $expected) {
    $r= $this->run('class <T> {
      public function run($arg) {
        return match ($arg) {
          200, 201, 202, 203, 204 => "OK",
          300, 301, 302, 303, 307 => "Redirect",
          default => "Error #$arg",
        };
      }
    }', $input);

    Assert::equals($expected, $r);
  }

  #[Test, Values([['PING', '+PONG'], ['MSG', '+OK Re: Test'], ['XFER', '-ERR Unknown XFER']])]
  public function match_with_multiple_statements($input, $expected) {
    $r= $this->run('class <T> {
      public function run($type) {
        $value= "Test";
        return match ($type) {
          "PING" => "+PONG",
          "MSG"  => {
            $reply= "Re: ".$value;
            return "+OK $reply";
          },
          default => {
            return "-ERR Unknown ".$type;
          }
        };
      }
    }', $input);

    Assert::equals($expected, $r);
  }

  #[Test, Values([[0, 'no items'], [1, 'one item'], [5, '5 items'], [10, '10+ items'],])]
  public function match_with_binary($input, $expected) {
    $r= $this->run('class <T> {
      public function run($arg) {
        return match (true) {
          $arg >= 10 => "10+ items",
          $arg === 1 => "one item",
          $arg === 0 => "no items",
          default    => $arg." items",
        };
      }
    }', $input);

    Assert::equals($expected, $r);
  }

  #[Test]
  public function match_allows_dropping_true() {
    $r= $this->run('class <T> {
      public function run($arg) {
        return match {
          $arg >= 10 => "10+ items",
          $arg === 1 => "one item",
          $arg === 0 => "no items",
          default    => $arg." items",
        };
      }
    }', 10);

    Assert::equals('10+ items', $r);
  }

  #[Test, Expect(class: Throwable::class, withMessage: '/Unhandled match (value of type .+|case .+)/')]
  public function unhandled_match() {
    $this->run('class <T> {
      public function run($arg) {
        $position= 1;
        return match ($arg) {
          SEEK_SET => 10,
          SEEK_CUR => $position + 10,
        };
      }
    }', SEEK_END);
  }

  #[Test, Expect(class: Throwable::class, withMessage: '/Unknown seek mode .+/')]
  public function match_with_throw_expression() {
    $this->run('class <T> {
      public function run($arg) {
        $position= 1;
        return match ($arg) {
          SEEK_SET => 10,
          SEEK_CUR => $position + 10,
          default  => throw new \\lang\\IllegalArgumentException("Unknown seek mode ".$arg)
        };
      }
    }', SEEK_END);
  }

  #[Test]
  public function match_without_arg_inside_fn() {
    $r= $this->run('class <T> {
      public function run() {
        return fn($arg) => match {
          $arg >= 10 => "10+ items",
          $arg === 1 => "one item",
          $arg === 0 => "no items",
          default    => $arg." items",
        };
      }
    }');

    Assert::equals('10+ items', $r(10));
  }

  #[Test]
  public function match_with_arg_inside_fn() {
    $r= $this->run('class <T> {
      public function run() {
        return fn($arg) => match ($arg) {
          0 => "no items",
          1 => "one item",
          default => $arg." items",
        };
      }
    }');

    Assert::equals('one item', $r(1));
  }
}