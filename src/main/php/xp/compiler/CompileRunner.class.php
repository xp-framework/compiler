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
 * @see  https://github.com/xp-framework/rfc/issues/299
 */
class CompileRunner {

  /** @return int */
  public static function main(array $args) {
    if (empty($args)) {
      Console::$err->writeLine('Usage: xp compile <in> [<out>]');
      return 2;
    }

    $in= '-' === $args[0] ? new ConsoleInputStream(STDIN) : new FileInputStream($args[0]);
    $out= !isset($args[1]) ? new ConsoleOutputStream(STDOUT) : new FileOutputStream($args[1]);
    $emit= Emitter::forRuntime(PHP_VERSION);

    $t= (new Timer())->start();
    try {
      $parse= new Parse(new Tokens(new StreamTokenizer($in)));
      $emitter= $emit->newInstance($out);
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