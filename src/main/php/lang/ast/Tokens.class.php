<?php namespace lang\ast;

use lang\FormatException;
use text\Tokenizer;

class Tokens implements \IteratorAggregate {
  const DELIMITERS = " |&^?!.:;,@%~=<>(){}[]#+-*/'\$\"\r\n\t";

  private static $operators= [
    '<' => ['<=', '<<', '<>', '<?', '<=>', '<<='],
    '>' => ['>=', '>>', '>>='],
    '=' => ['=>', '==', '==='],
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
    '?' => ['?:', '??', '?->', '??='],
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
    while (null !== ($token= $this->source->nextToken())) {
      if ('$' === $token) {
        yield 'variable' => [$this->source->nextToken(), $line];
      } else if ('"' === $token || "'" === $token) {
        $string= $token;
        $end= '\\'.$token;
        do {
          $t= $this->source->nextToken($end);
          if (null === $t) {
            throw new FormatException('Unclosed string literal starting at line '.$line);
          } else if ('\\' === $t) {
            $string.= $t.$this->source->nextToken($end);
          } else {
            $string.= $t;
          }
        } while ($token !== $t);

        yield 'string' => [$string, $line];
        $line+= substr_count($string, "\n");
      } else if ("\n" === $token) {
        $line++;
      } else if ("\r" === $token || "\t" === $token || ' ' === $token) {
        // Skip
      } else if (0 === strcspn($token, '0123456789')) {
        if ('.' === ($next= $this->source->nextToken())) {
          yield 'decimal' => [str_replace('_', '', $token.$next.$this->source->nextToken()), $line];
        } else {
          $this->source->pushBack($next);
          yield 'integer' => [str_replace('_', '', $token), $line];
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
            } while ('*' !== $t[strlen($t)- 1] && $this->source->hasMoreTokens());
            $comment.= $this->source->nextToken('/');
            yield 'comment' => [trim(preg_replace('/\n\s+\* ?/', "\n", substr($comment, 1, -2))), $line];
            $line+= substr_count($comment, "\n");
            continue;
          }
          $this->source->pushBack($next);
        } else if ('#' === $token) {
          $comment= $this->source->nextToken("\r\n").$this->source->nextToken("\r\n");
          $next= '#';
          do {
            $s= strspn($next, ' ');
            if ('#' !== $next[$s]) break;
            $line++;
            $comment.= substr($next, $s + 1);
            $next= $this->source->nextToken("\r\n").$this->source->nextToken("\r\n");
          } while ($this->source->hasMoreTokens());
          if (0 === strncmp($comment, '[@', 2)) {
            $this->source->pushBack(substr($comment, 1).$next);
            yield 'operator' => ['#[', $line];
          } else {
            $this->source->pushBack($next);
          }
          continue;
        }

        if (isset(self::$operators[$token])) {
          $combined= $token;
          foreach (self::$operators[$token] as $operator) {
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