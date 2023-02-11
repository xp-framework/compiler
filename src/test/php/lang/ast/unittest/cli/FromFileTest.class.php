<?php namespace lang\ast\unittest\cli;

use io\streams\FileInputStream;
use io\{File, Folder};
use lang\Environment;
use test\{After, Assert, Before, Test};
use xp\compiler\FromFile;

class FromFileTest {
  private $folder, $file;

  #[Before]
  public function folder() {
    $this->folder= new Folder(Environment::tempDir(), '.xp-'.crc32(self::class));
    $this->folder->exists() && $this->folder->unlink();
    $this->folder->create();

    $this->file= new File($this->folder, 'Test.php');
    $this->file->touch();
  }

  #[After]
  public function cleanup() {
    $this->file->isOpen() && $this->file->close();
    $this->folder->unlink();
  }

  #[Test]
  public function can_create() {
    new FromFile($this->file);
  }

  #[Test]
  public function can_create_from_string() {
    new FromFile($this->file->getURI());
  }

  #[Test]
  public function iteration() {
    $results= [];
    foreach (new FromFile($this->file) as $path => $stream) {
      $results[(string)$path]= get_class($stream);
    }

    Assert::equals([$this->file->getFileName() => FileInputStream::class], $results);
  }
}