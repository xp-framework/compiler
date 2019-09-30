<?php namespace lang\ast;

class Parse {
  private $tokens, $file;
  private $errors= [];

  public $token, $scope;
  public $comment= null;
  public $queue= [];

  /**
   * Creates a new parse instance
   *
   * @param  lang.ast.Language $language
   * @param  lang.ast.Tokens $tokens
   * @param  string $file
   */
  public function __construct($language, $tokens, $file= null) {
    $this->language= $language;
    $this->tokens= $tokens->getIterator();
    $this->file= $file;
    $this->scope= new Scope(null);
  }

  /**
   * Raise an error
   *
   * @param  string $error
   * @param  string $context
   * @param  int $line
   * @return void
   */
  public function raise($message, $context= null, $line= null) {
    $context && $message.= ' in '.$context;
    $this->errors[]= new Error($message, $this->file, $line ?: $this->token->line);
  }

  /**
   * Emit a warning
   *
   * @param  string $error
   * @param  string $context
   * @return void
   */
  public function warn($message, $context= null) {
    $context && $message.= ' ('.$context.')';
    trigger_error($message.' in '.$this->file.' on line '.$this->token->line);
  }

  /**
   * Forward this parser to the next token
   *
   * @return void
   */
  public function forward() {
    static $line= 1;

    if ($this->queue) {
      $this->token= array_shift($this->queue);
      return;
    }

    while ($this->tokens->valid()) {
      $type= $this->tokens->key();
      list($value, $line)= $this->tokens->current();
      $this->tokens->next();
      if ('name' === $type) {
        $t= new Token($this->language->symbols[$value] ?? $this->language->symbol('(name)'));
        $t->kind= $type;
      } else if ('operator' === $type) {
        $t= new Token($this->language->symbol($value));
        $t->kind= $type;
      } else if ('string' === $type || 'integer' === $type || 'decimal' === $type) {
        $t= new Token($this->language->symbol('(literal)'));
        $t->kind= 'literal';
      } else if ('variable' === $type) {
        $t= new Token($this->language->symbol('(variable)'));
        $t->kind= 'variable';
      } else if ('comment' === $type) {
        $this->comment= $value;
        continue;
      } else {
        throw new Error('Unexpected token '.$value, $this->file, $line);
      }

      $t->value= $value;
      $t->line= $line;
      $this->token= $t;
      return;
    }

    $t= new Token($this->language->symbol('(end)'));
    $t->line= $line;
    $this->token= $t;
  }

  /**
   * Forward expecting a given token, raise an error if another is encountered
   *
   * @param  string $id
   * @param  string $context
   * @return void
   */
  public function expecting($id, $context) {
    if ($id === $this->token->symbol->id) {
      $this->forward();
      return;
    }

    $message= sprintf(
      'Expected "%s", have "%s" in %s',
      $id,
      $this->token->value ?: $this->token->symbol->id,
      $context
    );
    $e= new Error($message, $this->file, $this->token->line);

    // Ensure we stop if we encounter the end
    if (null === $this->token->value) {
      throw $e;
    } else {
      $this->errors[]= $e;
    }
  }

  /**
   * Parses given file, returning AST nodes.
   *
   * @return iterable
   * @throws lang.ast.Errors
   */
  public function execute() {
    $this->forward();
    try {
      while (null !== $this->token->value) {
        if (null === ($statement= $this->language->statement($this))) break;
        yield $statement;
      }
    } catch (Error $e) {
      $this->errors[]= $e;
    }

    if ($this->errors) {
      throw new Errors($this->errors, $this->file);
    }
  }
}