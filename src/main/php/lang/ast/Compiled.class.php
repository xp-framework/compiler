<?php namespace lang\ast;

use io\streams\MemoryOutputStream;
use lang\ClassFormatException;
use lang\ast\transform\Transformations;
use text\StreamTokenizer;

class Compiled {
  public static $source= [];
  public static $emit;

  private $compiled, $offset;

  /**
   * Opens path
   *
   * @param  string $path
   * @param  string $mode
   * @param  int $options
   * @param  string $opened
   */
  public function stream_open($path, $mode, $options, &$opened) {
    $opened= substr($path, strpos($path, '://') + 3);
    $in= self::$source[$opened]->getResourceAsStream($opened)->in();

    $declaration= new MemoryOutputStream();
    try {
      $parse= new Parse(new Tokens(new StreamTokenizer($in)), $opened);
      $emitter= self::$emit->newInstance($declaration);
      foreach (Transformations::registered() as $kind => $function) {
        $emitter->transform($kind, $function);
      }
      $emitter->emit($parse->execute());

      $this->compiled= $declaration->getBytes();
    } catch (Error $e) {
      $message= sprintf('Syntax error in %s, line %d: %s', $e->getFile(), $e->getLine(), $e->getMessage());
      throw new ClassFormatException($message);
    } finally {
      $in->close();
    }

    $this->offset= 0;
    return true;
  }

  /**
   * Reads bytes
   *
   * @param  int $count
   * @return string
   */
  public function stream_read($count) {
    $chunk= substr($this->compiled, $this->offset, $count);
    $this->offset+= $count;
    return $chunk;
  }

  /** @return [:var] */
  public function url_stat($path) {
    $opened= substr($path, strpos($path, '://') + 3);
    return ['size' => self::$source[$opened]->getResourceAsStream($opened)->size()];
  }

  /** @return [:var] */
  public function stream_stat() {
    return ['size' => strlen($this->compiled)];
  }

  /** @return bool */
  public function stream_eof() {
    return $this->offset >= strlen($this->compiled);
  }

  /** @return void */
  public function stream_close() {
    // NOOP
  }

  /** @return void */
  public function stream_flush() {
    // NOOP
  }
}