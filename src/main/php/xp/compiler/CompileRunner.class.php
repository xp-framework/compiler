<?php namespace xp\compiler;

use io\streams\FileInputStream;
use io\streams\ConsoleInputStream;
use io\streams\ConsoleOutputStream;
use text\StreamTokenizer;
use lang\ast\Tokens;
use lang\ast\Parse;
use lang\ast\Error;
use lang\ast\Emitter;
use util\cmd\Console;
use util\profiling\Timer;
use lang\ast\transform\Transformations;

/**
 * Compiles future PHP to today's PHP.
 *
 * - Compile code and write result to a class file
 *   ```sh
 *   $ xp compile HelloWorld.php HelloWorld.class.php
 *   ```
 *
 * - Compile standard input and write to standard output.
 *   ```sh
 *   $ echo "<?php ..." | xp compile -
 *   ```
 *
 * - Target PHP 5.6 (default target is current PHP version)
 *   ```sh
 *   $ xp compile -t 5.6 HelloWorld.php HelloWorld.class.php
 *   ```
 *
 * @see  https://github.com/xp-framework/rfc/issues/299
 */
class CompileRunner {

  /** @return int */
  public static function main(array $args) {
    if (empty($args)) {
      Console::$err->writeLine('Usage: xp compile <in> [<out>]');
      return 2;
    }

    $target= PHP_VERSION;
    for ($i= 0; $i < sizeof($args); $i++) {
      if ('-t' === $args[$i]) {
        $target= $args[++$i];
      } else {
        break;
      }
    }

    $in= '-' === $args[$i] ? new ConsoleInputStream(STDIN) : new FileInputStream($args[$i]);
    $out= !isset($args[$i + 1]) ? new ConsoleOutputStream(STDOUT) : new FileOutputStream($args[$i + 1]);
    $emit= Emitter::forRuntime($target);

    $t= (new Timer())->start();
    try {
      $parse= new Parse(new Tokens(new StreamTokenizer($in)));
      $emitter= $emit->newInstance($out);
      foreach (Transformations::registered() as $kind => $function) {
        $emitter->transform($kind, $function);
      }
      $emitter->emit($parse->execute());

      Console::$err->writeLinef(
        "\033[32;1mSuccessfully compiled %s using %s in %.3f seconds\033[0m",
        $args[0],
        $emit->getName(),
        $t->elapsedTime()
      );
      return 0;
    } catch (Error $e) {
      Console::$err->writeLinef(
        "\033[31;1mCompile error: %s of %s\033[0m",
        $e->getMessage(),
        $args[0]
      );
      return 1;
    } finally {
      $in->close();
    }
  }
}