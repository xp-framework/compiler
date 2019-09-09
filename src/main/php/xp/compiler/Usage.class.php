<?php namespace xp\compiler;

use lang\ast\Language;
use util\cmd\Console;

class Usage {

  /** @return int */
  public static function main(array $args) {
    Console::$err->writeLine('Usage: xp compile <in> [<out>]');
    Console::$err->writeLine();

    // Show syntax implementations sorted by class loader
    $loaders= $sorted= [];
    foreach (Language::named('PHP')->extensions() as $extension) {
      $t= typeof($extension);
      $l= $t->getClassLoader();
      $hash= $l->hashCode();
      if (isset($sorted[$hash])) {
        $sorted[$hash][]= $t;
      } else {
        $loaders[$hash]= $l;
        $sorted[$hash]= [$t];
      }
    }
    foreach ($sorted as $hash => $list) {
      Console::$err->writeLine("\033[33m@", $loaders[$hash], "\033[0m");
      foreach ($list as $syntax) {
        Console::$err->writeLine($syntax->getName());
      }
    }
    return 2;
  }
}