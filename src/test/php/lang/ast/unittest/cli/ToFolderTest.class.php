<?php namespace lang\ast\unittest\cli;

use io\{Folder, File};
use lang\Environment;
use lang\FileSystemClassLoader;
use unittest\{After, Assert, Before, Test};
use xp\compiler\ToFolder;

class ToFolderTest {
  private $folder;

  #[Before]
  public function folder() {
    $this->folder= new Folder(realpath(Environment::tempDir()), '.xp-'.crc32(self::class));
    $this->folder->exists() && $this->folder->unlink();
    $this->folder->create();
  }

  #[After]
  public function cleanup() {
    $this->folder->unlink();
  }

  #[Test]
  public function can_create() {
    new ToFolder($this->folder);
  }

  #[Test]
  public function dash_special_case() {
    with ((new ToFolder($this->folder))->target('-'), function($out) {
      $out->write('<?php ...');
    });
    Assert::true((new File($this->folder, 'out'.\xp::CLASS_FILE_EXT))->exists());
  }

  #[Test]
  public function write_to_target_then_load_via_class_loader() {
    $class= '<?php class Test { }';

    $fixture= new ToFolder($this->folder);
    with ($fixture->target('Test.php'), function($out) use($class) {
      $out->write($class);
      $out->flush();
      $out->close();
    });
    $fixture->close();

    Assert::equals($class, (new FileSystemClassLoader($this->folder->getURI()))->loadClassBytes('Test'));
  }
}