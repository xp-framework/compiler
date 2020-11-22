<?php namespace xp\compiler;

use io\File;
use io\streams\OutputStream;
use lang\archive\Archive;

class ToArchive extends Output {
  private $archive;

  /** @param string $file */
  public function __construct($file) {
    $this->archive= new Archive($file instanceof File ? $file : new File($file));
    $this->archive->open(Archive::CREATE);
  }

  /**
   * Returns the target for a given input 
   *
   * @param  string $name
   * @return io.streams.OutputStream
   */
  public function target($name) {
    return new class($this->archive, $name) implements OutputStream {
      private static $replace= [DIRECTORY_SEPARATOR => '/', '.php' => \xp::CLASS_FILE_EXT];

      private $bytes= '';
      private $archive, $name;

      public function __construct($archive, $name) { $this->archive= $archive; $this->name= $name; }
      public function write($bytes) { $this->bytes.= $bytes; }
      public function flush() {  }
      public function close() { $this->archive->addBytes(strtr($this->name, self::$replace), $this->bytes); }
    };
  }

  /** @return void */
  public function close() {
    $this->archive->create();
  }
}