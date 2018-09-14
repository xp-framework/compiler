<?php namespace lang\ast;

use io\streams\OutputStream;
use lang\ClassFormatException;
use lang\ast\transform\Transformations;
use text\StreamTokenizer;

class Compiled implements OutputStream {
  public static $source= [], $emit= [];

  private $compiled= '', $offset= 0;

  /**
   * Opens path
   *
   * @param  string $path
   * @param  string $mode
   * @param  int $options
   * @param  string $opened
   */
  public function stream_open($path, $mode, $options, &$opened) {
    list($version, $file)= explode('://', $path);
    $stream= self::$source[$file]->getResourceAsStream($file);
    $in= $stream->in();

    try {
      $parse= new Parse(new Tokens(new StreamTokenizer($in)), $file);
      $emitter= self::$emit[$version]->newInstance($this);
      foreach (Transformations::registered() as $kind => $function) {
        $emitter->transform($kind, $function);
      }
      $emitter->emit($parse->execute());
      $opened= $stream->getURI();
      return true;
    } catch (Error $e) {
      $message= sprintf('Syntax error in %s, line %d: %s', $e->getFile(), $e->getLine(), $e->getMessage());
      throw new ClassFormatException($message);
    } finally {
      $in->close();
    }
  }

  /** @param string $bytes */
  public function write($bytes) {
    $this->compiled.= $bytes;
  }

  /** @return void */
  public function flush() {
    // NOOP
  }

  /** @return void */
  public function close() {
    // NOOP
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