<?php namespace xp\compiler;

use lang\reflect\Package;
use util\cmd\Console;

class Usage {

  /** @return int */
  public static function main(array $args) {
    Console::$err->writeLine('Usage: xp compile <in> [<out>]');
    Console::$err->writeLine();

    // Show syntax implementations sorted by class loader
    $loaders= $sorted= [];
    foreach (Package::forName('lang.ast.syntax')->getClasses() as $syntax) {
      $l= $syntax->getClassLoader();
      $hash= $l->hashCode();
      if (isset($sorted[$hash])) {
        $sorted[$hash][]= $syntax;
      } else {
        $loaders[$hash]= $l;
        $sorted[$hash]= [$syntax];
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