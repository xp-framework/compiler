<?php namespace lang\ast;

use io\streams\OutputStream;
use text\StreamTokenizer;

class Compiled implements OutputStream {
  public static $source= [], $emit= [], $lang;

  private $compiled= '', $offset= 0;

  public static function bytes($version, $source, $file) {
    $stream= $source->getResourceAsStream($file);
    return self::parse($version, $stream->in(), new self(), $file)->compiled;
  }

  private static function parse($version, $in, $out, $file) {
    try {
      $parse= new Parse(self::$lang, new Tokens(new StreamTokenizer($in)), $file);
      self::$emit[$version]->emit(new Result($out), $parse->execute());
      return $out;
    } finally {
      $in->close();
    }
  }

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
    self::parse($version, $stream->in(), $this, $file);
    $opened= $stream->getURI();
    return true;
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

  /**
   * Stream wrapper method stream_set_option
   *
   * @param  int $option
   * @param  int $arg1
   * @param  int $arg2
   * @return bool
   */
  public function stream_set_option($option, $arg1, $arg2) {
    return true;
  }
}