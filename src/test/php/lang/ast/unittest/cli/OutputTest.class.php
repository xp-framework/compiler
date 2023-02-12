<?php namespace lang\ast\unittest\cli;

use io\{File, Folder};
use lang\Environment;
use test\{After, Assert, Before, Test, Values};
use util\cmd\Console;
use xp\compiler\{CompileOnly, Output, ToArchive, ToFile, ToFolder, ToStream};

class OutputTest {
  private $folder, $file, $archive;

  #[Before]
  public function folder() {
    $this->folder= new Folder(realpath(Environment::tempDir()), '.xp-'.crc32(self::class));
    $this->folder->exists() && $this->folder->unlink();
    $this->folder->create();

    $this->file= new File($this->folder, 'Test.php');
    $this->file->touch();

    $this->archive= new File($this->folder, 'dist.xar');
    $this->archive->touch();
  }

  #[After]
  public function cleanup() {
    $this->folder->unlink();
  }

  #[Test]
  public function compile_only() {
    Assert::equals(new CompileOnly(), Output::newInstance(null));
  }

  #[Test]
  public function to_stdin() {
    Assert::equals(new ToStream(Console::$out->stream()), Output::newInstance('-'));
  }

  #[Test]
  public function to_file() {
    Assert::equals(new ToFile($this->file), Output::newInstance($this->file->getURI()));
  }

  #[Test]
  public function to_archive() {
    Assert::equals(new ToArchive($this->archive), Output::newInstance($this->archive->getURI()));
  }

  #[Test]
  public function to_folder() {
    Assert::equals(new ToFolder($this->folder), Output::newInstance($this->folder->getURI()));
  }
}