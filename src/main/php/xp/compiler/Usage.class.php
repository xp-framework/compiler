<?php namespace xp\compiler;

use lang\ast\{Language, Emitter};
use lang\reflect\Package;
use util\cmd\Console;

class Usage {

  /** @return int */
  public static function main(array $args) {
    Console::$err->writeLine('Usage: xp compile <in> [<out>]');

    $impl= new class() {
      public $byLoader= [];

      public function add($t, $active= false) {
        $this->byLoader[$t->getClassLoader()->toString()][$t->getName()]= $active;
      }
    };

    $default= Emitter::forRuntime('PHP.'.PHP_VERSION);
    foreach (Package::forName('lang.ast.emit')->getClasses() as $class) {
      if ($class->isSubclassOf(Emitter::class) && !(MODIFIER_ABSTRACT & $class->getModifiers())) {
        $impl->add($class, $class->equals($default));
      }
    }

    foreach (Language::named('PHP')->extensions() as $extension) {
      $impl->add(typeof($extension), true);
    }

    // Show implementations sorted by class loader
    foreach ($impl->byLoader as $loader => $list) {
      Console::$err->writeLine();
      Console::$err->writeLine("\033[33m@", $loader, "\033[0m");
      foreach ($list as $impl => $active) {
        Console::$err->writeLine($impl, $active ? " [\033[36m*\033[0m]" : '');
      }
    }
    return 2;
  }
}