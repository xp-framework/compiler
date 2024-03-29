<?php namespace xp\compiler;

use io\File;
use io\streams\OutputStream;
use lang\archive\Archive;

/** Writes compiled files to a .XAR archive */
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
    return new class($this->archive, $name, $this->extension) implements OutputStream {
      private $archive, $name, $replace;
      private $bytes= '';

      public function __construct($archive, $name, $extension) {
        $this->archive= $archive;
        $this->name= $name;
        $this->replace= [DIRECTORY_SEPARATOR => '/', '.php' => $extension];
      }

      public function write($bytes) { $this->bytes.= $bytes; }

      public function flush() {  }

      public function close() { $this->archive->addBytes(strtr($this->name, $this->replace), $this->bytes); }
    };
  }

  /** @return void */
  public function close() {
    $this->archive->create();
  }
}