<?php namespace xp\compiler;

use lang\ast\Emitter;
use lang\ast\Error;
use lang\ast\Parse;
use lang\ast\Tokens;
use lang\ast\transform\Transformations;
use lang\IllegalArgumentException;
use lang\Runtime;
use text\StreamTokenizer;
use util\cmd\Console;
use util\profiling\Timer;

/**
 * Compiles future PHP to today's PHP.
 *
 * - Compile code and write result to a class file
 *   ```sh
 *   $ xp compile HelloWorld.php HelloWorld.class.php
 *   ```
 * - Compile standard input and write to standard output.
 *   ```sh
 *   $ echo "<?php ..." | xp compile -
 *   ```
 * - Compile all files inside `src/main/php` to the `dist` folder.
 *   ```sh
 *   $ xp compile src/main/php dist/
 *   ```
 * - Target PHP 5.6 (default target is current PHP version)
 *   ```sh
 *   $ xp compile -t 5.6 HelloWorld.php HelloWorld.class.php
 *   ```
 *
 * @see  https://github.com/xp-framework/rfc/issues/299
 */
class CompileRunner {

  /**
   * Returns input from the command line argument
   *
   * @param  string $arg
   * @param  string $base
   * @return iterable
   * @throws lang.IllegalArgumentException
   */
  private static function input($arg, $base) {
    if ('-' === $arg) {
      return ['(standard input)' => Console::$in->getStream()];
    } else if (is_file($arg)) {
      return new SourceFile($arg, $base ?: '.');
    } else if (is_dir($arg)) {
      return new SourceFilesIn($arg, $base ?: $arg);
    } else {
      throw new IllegalArgumentException('Expecting either - for standard input, a file or a folder as input');
    }
  }

  /**
   * Returns output from the command line argument
   *
   * @param  string $arg
   * @return xp.compiler.Output
   */
  private static function output($arg) {
    if ('-' === $arg) {
      return new ToStream(Console::$out->getStream());
    } else if (strstr($arg, '.php')) {
      return new ToFile($arg);
    } else {
      return new ToFolder($arg);
    }
  }

  /** @return int */
  public static function main(array $args) {
    if (empty($args)) {
      Console::$err->writeLine('Usage: xp compile <in> [<out>]');
      return 2;
    }

    $target= PHP_VERSION;
    $base= null;
    for ($i= 0; $i < sizeof($args); $i++) {
      if ('-t' === $args[$i]) {
        $target= $args[++$i];
      } else if ('-b' === $args[$i]) {
        $base= $args[++$i];
      } else {
        break;
      }
    }

    $out= isset($args[$i + 1]) ? $args[$i + 1] : '-';
    $emit= Emitter::forRuntime($target);
    $input= self::input($args[$i], $base);
    $output= self::output($out);

    $t= new Timer();
    $total= $errors= 0;
    $time= 0.0;
    foreach ($input as $name => $in) {
      $t->start();
      try {
        $parse= new Parse(new Tokens(new StreamTokenizer($in)));
        $emitter= $emit->newInstance($output->target($name));
        foreach (Transformations::registered() as $kind => $function) {
          $emitter->transform($kind, $function);
        }
        $emitter->emit($parse->execute());

        $t->stop();
        Console::$err->writeLinef('> %s (%.3f seconds)', $name, $t->elapsedTime());
      } catch (Error $e) {
        $t->stop();
        Console::$err->writeLinef('! %s: %s', $name, $e->toString());
        $errors++;
      } finally {
        $total++;
        $time+= $t->elapsedTime();
        $in->close();
      }
    }

    Console::$err->writeLine();
    Console::$err->writeLinef(
      "%s Compiled %d file(s) to %s using %s, %d error(s) occurred\033[0m",
      $errors ? "\033[41;1;37m×" : "\033[42;1;37m♥",
      $total,
      $out,
      $emit->getName(),
      $errors
    );
    Console::$err->writeLinef(
      "Memory used: %.2f kB (%.2f kB peak)\nTime taken: %.3f seconds",
      Runtime::getInstance()->memoryUsage() / 1024,
      Runtime::getInstance()->peakMemoryUsage() / 1024,
      $time
    );
    return $errors ? 1 : 0;
  }
}