<?php namespace lang\ast;

use lang\{IllegalArgumentException, IllegalStateException};

class Errors extends IllegalStateException {

  /**
   * Creates a new error
   *
   * @param  lang.ast.Error[] $errors
   * @param  string $file
   */
  public function __construct($errors, $file) {
    switch (sizeof($errors)) {
      case 0:
        throw new IllegalArgumentException('Errors may not be empty!');

      case 1:
        $message= $errors[0]->getMessage();
        break;

      default: 
        $message= "Errors {\n";
        foreach ($errors as $error) {
          $message.= '  '.$error->message.' at line '.$error->line."\n";
        }
        $message.= '}';
    }

    parent::__construct($message);
    $this->file= $file;
    $this->line= $errors[0]->getLine();
  }

  /**
   * Returns diagnostics
   *
   * @param  string $indent
   * @return string
   */
  public function diagnostics($indent= '') {
    return str_replace("\n", "\n".$indent, $this->message);
  }
}