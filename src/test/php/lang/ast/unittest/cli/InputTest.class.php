<?php namespace lang\ast\unittest\cli;

use io\{File, Folder};
use lang\{Environment, IllegalArgumentException};
use unittest\{Assert, After, Before, Test, Values};
use util\cmd\Console;
use xp\compiler\{Input, FromStream, FromFile, FromFilesIn, FromInputs};

class InputTest {
  private $folder, $file;

  #[Before]
  public function folder() {
    $this->folder= new Folder(realpath(Environment::tempDir()), '.xp-'.crc32(self::class));
    $this->folder->exists() && $this->folder->unlink();
    $this->folder->create();

    $this->file= new File($this->folder, 'Test.php');
    $this->file->touch();
  }

  #[After]
  public function cleanup() {
    $this->folder->unlink();
  }

  #[Test]
  public function from_stdin() {
    Assert::equals(new FromStream(Console::$in->getStream(), '-'), Input::newInstance('-'));
  }

  #[Test]
  public function from_file() {
    Assert::equals(new FromFile($this->file), Input::newInstance($this->file->getURI()));
  }

  #[Test]
  public function from_folder() {
    Assert::equals(new FromFilesIn($this->folder), Input::newInstance($this->folder->getURI()));
  }

  #[Test]
  public function from_empty_array() {
    Assert::equals(new FromInputs([]), Input::newInstance([]));
  }

  #[Test]
  public function from_array() {
    $array= ['-', $this->file];
    Assert::equals(new FromInputs($array), Input::newInstance($array));
  }

  #[Test, Expect(IllegalArgumentException::class)]
  public function from_illegal_argument() {
    Input::newInstance('');
  }
}