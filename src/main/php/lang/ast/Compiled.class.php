<?php namespace lang\ast;

use io\streams\OutputStream;

class Compiled implements OutputStream {
  public static $source= [], $emit= [], $lang= [];

  private $compiled= '', $offset= 0;
  public $context;

  public static function bytes($version, $source, $file) {
    $stream= $source[1]->getResourceAsStream($file);
    return self::parse($source[0], $stream->in(), $version, new self(), $file)->compiled;
  }

  private static function language($name, $emitter) {
    $language= Language::named(strtoupper($name));
    foreach ($language->extensions() as $extension) {
      $extension->setup($language, $emitter);
    }
    return $language;
  }

  private static function parse($lang, $in, $version, $out, $file) {
    $language= self::$lang[$lang] ?? self::$lang[$lang]= self::language($lang, self::$emit[$version]);
    try {
      return self::$emit[$version]->write($language->parse(new Tokens($in, $file))->stream(), $out);
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
    $stream= self::$source[$file][1]->getResourceAsStream($file);
    self::parse(self::$source[$file][0], $stream->in(), $version, $this, $file);
    $opened= $stream->getURI();
    return true;
  }

  /** @param string $bytes */
  public function write($bytes) {
    $this->compiled.= $bytes;
  }

  /** @codeCoverageIgnore */
  public function flush() {
    // NOOP
  }

  /** @codeCoverageIgnore */
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