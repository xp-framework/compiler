<?php namespace lang\ast;

use text\Tokenizer;
use lang\FormatException;

class Tokens implements \IteratorAggregate {
  const DELIMITERS = " |&^?!.:;,@%~=<>(){}[]#+-*/'\$\"\r\n\t";

  private $operators= [
    '<' => ['<=', '<<', '<>', '<?', '<=>', '<<='],
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
    $line= 1;
    while ($this->source->hasMoreTokens()) {
      $token= $this->source->nextToken();
      if ('$' === $token) {
        yield 'variable' => [$this->source->nextToken(), $line];
      } else if ('"' === $token || "'" === $token) {
        $string= $token;
        do {
          // Empty string, e.g. "" or ''
          if ($token === ($t= $this->source->nextToken($token))) break;

          $string.= $t;
          $l= strlen($string);
          if ($l > 0 && '\\' === $string{$l - 1} && !($l > 1 && '\\' === $string{$l - 2})) {
            $string.= $this->source->nextToken($token);
            continue;
          }
          if ($token !== $this->source->nextToken($token)) {
            throw new FormatException('Unclosed string literal starting at line '.$line);
          }
          break;
        } while ($this->source->hasMoreTokens());

        yield 'string' => [$string.$token, $line];
        $line+= substr_count($string, "\n");
      } else if (0 === strcspn($token, " \r\n\t")) {
        $line+= substr_count($token, "\n");
        continue;
      } else if (0 === strcspn($token, '0123456789')) {
        if ('.' === ($next= $this->source->nextToken())) {
          yield 'decimal' => [$token.$next.$this->source->nextToken(), $line];
        } else {
          $this->source->pushBack($next);
          yield 'integer' => [$token, $line];
        }
      } else if (0 === strcspn($token, self::DELIMITERS)) {
        if ('.' === $token) {
          $next= $this->source->nextToken();
          if (0 === strcspn($next, '0123456789')) {
            yield 'decimal' => [".$next", $line];
            continue;
          }
          $this->source->pushBack($next);
        } else if ('/' === $token) {
          $next= $this->source->nextToken();
          if ('/' === $next) {
            $this->source->nextToken("\r\n");
            continue;
          } else if ('*' === $next) {
            $comment= '';
            do {
              $t= $this->source->nextToken('/');
              $comment.= $t;
            } while ('*' !== $t{strlen($t)- 1} && $this->source->hasMoreTokens());
            $comment.= $this->source->nextToken('/');
            $line+= substr_count($comment, "\n");
            yield 'comment' => [substr($comment, 2, -3), $line];
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
        yield 'operator' => [$token, $line];
      } else {
        yield 'name' => [$token, $line];
      }
    }
  }
}