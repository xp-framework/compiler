<?php namespace lang\ast;

use text\Tokenizer;
use lang\FormatException;

class Tokens implements \IteratorAggregate {
  const DELIMITERS = " |&?!.:;,@%~=<>(){}[]#+-*/'\$\"\r\n\t";

  private $operators= [
    '<' => ['<=', '<<', '<>', '<=>', '<<=', '<?php'],
    '>' => ['>=', '>>', '>>='],
    '=' => ['=>', '==', '==>', '==='],
    '!' => ['!=', '!=='],
    '&' => ['&&', '&='],
    '|' => ['||', '|='],
    '^' => ['^='],
    '+' => ['+=', '++'],
    '-' => ['-=', '--', '->'],
    '*' => ['*=', '**', '**='],
    '/' => ['/='],
    '~' => ['~='],
    '%' => ['%='],
    '?' => ['?:', '??'],
    '.' => ['.=', '...'],
    ':' => ['::'],
    "\303" => ["\303\227"]
  ]; 
  private $source;

  /**
   * Create new iterable tokens from a string or a stream tokenizer
   *
   * @param  text.Tokenizer $source
   */
  public function __construct(Tokenizer $source) {
    $this->source= $source;
    $this->source->delimiters= self::DELIMITERS;
    $this->source->returnDelims= true;
  }

  /** @return php.Iterator */
  public function getIterator() {
    while ($this->source->hasMoreTokens()) {
      $token= $this->source->nextToken();
      if ('$' === $token) {
        yield 'variable' => $this->source->nextToken();
      } else if ('"' === $token || "'" === $token) {
        $string= '';
        while ($this->source->hasMoreTokens()) {
          if ($token === ($part= $this->source->nextToken($token))) {
            yield 'string' => $string;
            continue 2;
          }
          $string.= $part;
        }
        throw new FormatException('Unclosed string literal');
      } else if (0 === strcspn($token, " \r\n\t")) {
        continue;
      } else if (0 === strcspn($token, '0123456789')) {
        if ('.' === ($next= $this->source->nextToken())) {
          yield 'decimal' => (float)($token.$next.$this->source->nextToken());
        } else {
          $this->source->pushBack($next);
          yield 'integer' => (int)$token;
        }
      } else if (0 === strcspn($token, self::DELIMITERS)) {
        if ('.' === $token) {
          $next= $this->source->nextToken();
          if (0 === strcspn($next, '0123456789')) {
            yield 'decimal' => (float)"0.$next";
            continue;
          }
          $this->source->pushBack($next);
        } else if ('/' === $token) {
          $next= $this->source->nextToken();
          if ('/' === $next) {
            $this->source->nextToken("\r\n");
            continue;
          } else if ('*' === $next) {
            do {
              $t= $this->source->nextToken('/');
            } while ('*' !== $t{strlen($t)- 1} && $this->source->hasMoreTokens());
            $this->source->nextToken('/');
            continue;
          }
          $this->source->pushBack($next);
        }

        if (isset($this->operators[$token])) {
          $combined= $token;
          foreach ($this->operators[$token] as $operator) {
            while (strlen($combined) < strlen($operator) && $this->source->hasMoreTokens()) {
              $combined.= $this->source->nextToken();
            }
            $combined === $operator && $token= $combined;
          }

          $this->source->pushBack(substr($combined, strlen($token)));
        }
        yield 'operator' => $token;
      } else {
        yield 'name' => $token;
      }
    }
  }
}