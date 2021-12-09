<?php namespace xp\compiler;

use io\Path;
use lang\Runtime;
use lang\ast\{CompilingClassloader, Emitter, Errors, Language, Result, Tokens};
use text\StreamTokenizer;
use util\cmd\Console;
use util\profiling\Timer;

/**
 * Compiles future PHP to today's PHP
 * ==================================
 *
 * - Compile code and write result to a class file
 *   ```sh
 *   $ xp compile HelloWorld.php HelloWorld.class.php
 *   ```
 * - Compile standard input and write to standard output.
 *   ```sh
 *   $ echo "<?php ..." | xp compile -
 *   ```
 * - Compile `src/main/php` and `src/test/php` to the `dist` folder.
 *   ```sh
 *   $ xp compile -o dist src/main/php/ src/test/php/
 *   ```
 * - Compile `src/main/php` to the `dist.xar` archive.
 *   ```sh
 *   $ xp compile -o dist.xar src/main/php/
 *   ```
 * - Compile `src/main/php`, do not write output
 *   ```sh
 *   $ xp compile -n src/main/php/
 *   ```
 * - Target PHP 7.4 (default target is current PHP version)
 *   ```sh
 *   $ xp compile -t php:7.4 HelloWorld.php HelloWorld.class.php
 *   ```
 *
 * The *-o* and *-n* options accept multiple input sources following them.
 * The *-q* option suppresses all diagnostic output except for errors.
 * 
 * @codeCoverageIgnore 
 * @see  https://github.com/xp-framework/rfc/issues/299
 */
class CompileRunner {

  /** @return int */
  public static function main(array $args) {
    if (empty($args)) return Usage::main($args);

    $target= 'php:'.PHP_VERSION;
    $in= $out= '-';
    $quiet= false;
    for ($i= 0; $i < sizeof($args); $i++) {
      if ('-t' === $args[$i]) {
        $target= $args[++$i];
      } else if ('-q' === $args[$i]) {
        $quiet= true;
      } else if ('-o' === $args[$i]) {
        $out= $args[++$i];
        $in= array_slice($args, $i + 1);
        break;
      } else if ('-n' === $args[$i]) {
        $out= null;
        $in= array_slice($args, $i + 1);
        break;
      } else {
        $in= $args[$i];
        $out= $args[$i + 1] ?? '-';
        break;
      }
    }

    $lang= Language::named('PHP');
    $emit= Emitter::forRuntime($target)->newInstance();
    foreach ($lang->extensions() as $extension) {
      $extension->setup($lang, $emit);
    }

    $input= Input::newInstance($in);
    $output= Output::newInstance($out);

    $t= new Timer();
    $total= $errors= 0;
    $time= 0.0;
    foreach ($input as $path => $source) {
      $file= $path->toString('/');
      $t->start();
      try {
        $parse= $lang->parse(new Tokens($source, $file));
        $target= $output->target((string)$path);
        $emit->emitAll(new Result($target), $parse->stream());

        $t->stop();
        $quiet || Console::$err->writeLinef('> %s (%.3f seconds)', $file, $t->elapsedTime());
      } catch (Errors $e) {
        $t->stop();
        Console::$err->writeLinef('! %s: %s ', $file, $e->diagnostics('  '));
        $errors++;
      } finally {
        $total++;
        $time+= $t->elapsedTime();
        $source->close();
        $target->close();
      }
    }

    if (!$quiet) {
      Console::$err->writeLine();
      Console::$err->writeLinef(
        "%s Compiled %d file(s) to %s using %s, %d error(s) occurred\033[0m",
        $errors ? "\033[41;1;37m×" : "\033[42;1;37m♥",
        $total,
        $out,
        typeof($emit)->getName(),
        $errors
      );
      Console::$err->writeLinef(
        "Memory used: %.2f kB (%.2f kB peak)\nTime taken: %.3f seconds",
        Runtime::getInstance()->memoryUsage() / 1024,
        Runtime::getInstance()->peakMemoryUsage() / 1024,
        $time
      );
    }

    $output->close();
    return $errors ? 1 : 0;
  }
}