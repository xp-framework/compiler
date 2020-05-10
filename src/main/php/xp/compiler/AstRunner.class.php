<?php namespace xp\compiler;

use lang\ast\{Emitter, Errors, Language, Node, Result, Tokens};
use util\Objects;
use util\cmd\Console;
/**
 * Display AST
 *
 * - Tokenize code and display abstract syntax tree:
 *   ```sh
 *   $ xp ast HelloWorld.php
 *   ```
 * - Tokenize standard input
 *   ```sh
 *   $ echo "<?php echo 1;" | xp ast -
 *   ```
 */
class AstRunner {

  private static function stringOf($value, $indent= '') {
    if ($value instanceof Node) {
      $p= [];
      foreach (get_object_vars($value) as $name => $prop) {
        if ('kind' === $name || 'line' === $name) continue;
        $p[$name]= $prop;
      }

      $s= -1 === $value->line
        ? sprintf("\033[36m%s\033[0m(\033[34m:%s\033[0m", nameof($value), $value->kind)
        : sprintf("\033[36m%s\033[0m(\033[32m#%03d\033[0m, \033[34m:%s\033[0m", nameof($value), $value->line, $value->kind)
      ;
      switch (sizeof($p)) {
        case 0: return $s.')';
        case 1: return $s.', '.key($p).'= '.self::stringOf(current($p), $indent).')';
        default: 
          $s.= ")@{\n";
          $i= $indent.'  ';
          foreach ($p as $name => $prop) {
            $s.= $i.$name.' => '.self::stringOf($prop, $i)."\n";
          }
          $s.= $indent.'}';
          return $s;
      }
    } else if (is_array($value)) {
      if (empty($value)) return '[]';
      $s= "[\n";
      $i= $indent.'  ';
      if (0 === key($value)) {
        foreach ($value as $val) {
          $s.= $i.self::stringOf($val, $i)."\n";
        }
      } else {
        foreach ($value as $key => $val) {
          $s.= $i.$key.' => '.self::stringOf($val, $i)."\n";
        }
      }
      return $s.$indent.']';
    } else if (is_string($value)) {
      return "\033[34m\"".$value."\"\033[0m";
    } else {
      return Objects::stringOf($value);
    }
  }

  /** @return int */
  public static function main(array $args) {
    if (empty($args)) {
      Console::writeLine('Usage: xp ast [file]');
    }

    $lang= Language::named('PHP');
    $emit= Emitter::forRuntime('PHP.'.PHP_VERSION)->newInstance();
    foreach ($lang->extensions() as $extension) {
      $extension->setup($lang, $emit);
    }

    $input= Input::newInstance($args[0]);
    $errors= 0;
    foreach ($input as $path => $in) {
      $file= $path->toString('/');
      Console::writeLinef("\033[1m══ %s ══%s\033[0m", $file, str_repeat('═', 72 - 6 - strlen($file)));

      try {
        foreach ($lang->parse(new Tokens($in, $file))->stream() as $node) {
          Console::writeLine(self::stringOf($node));
        }
      } catch (Errors $e) {
        Console::$err->writeLinef("\033[41;1;37m! %s: %s\033[0m", $file, $e->diagnostics('  '));
        $errors++;
      }
    }
    return $errors ? 1 : 0;
  }
}