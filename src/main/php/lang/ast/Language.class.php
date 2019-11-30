<?php namespace lang\ast;

use lang\ast\nodes\{Assignment, BinaryExpression, Literal, UnaryExpression};
use lang\ast\syntax\{Extension, TransformationApi};
use lang\reflect\Package;

/**
 * Base class for input languages
 */
abstract class Language {
  private static $instance= [];

  public $symbols= [];

  public function symbol($id, $lbp= 0) {
    if (isset($this->symbols[$id])) {
      $symbol= $this->symbols[$id];
      if ($lbp > $symbol->lbp) {
        $symbol->lbp= $lbp;
      }
    } else {
      $symbol= new Symbol();
      $symbol->id= $id;
      $symbol->lbp= $lbp;
      $this->symbols[$id]= $symbol;
    }
    return $symbol;
  }

  public function constant($id, $value) {
    $const= $this->symbol($id);
    $const->nud= function($parse, $token) use($value) {
      return new Literal($value, $token->line);
    };
  }

  public function assignment($id) {
    $infix= $this->symbol($id, 10);
    $infix->led= function($parse, $token, $left) use($id) {
      return new Assignment($left, $id, $this->expression($parse, 9), $token->line);
    };
  }

  public function infix($id, $bp, $led= null) {
    $infix= $this->symbol($id, $bp);
    $infix->led= $led ? $led->bindTo($this, static::class) : function($parse, $token, $left) use($id, $bp) {
      return new BinaryExpression($left, $id, $this->expression($parse, $bp), $token->line);
    };
  }

  public function infixr($id, $bp, $led= null) {
    $infix= $this->symbol($id, $bp);
    $infix->led= $led ? $led->bindTo($this, static::class) : function($parse, $token, $left) use($id, $bp) {
      return new BinaryExpression($left, $id, $this->expression($parse, $bp - 1), $token->line);
    };
  }

  public function prefix($id, $bp, $nud= null) {
    $prefix= $this->symbol($id);
    $prefix->nud= $nud ? $nud->bindTo($this, static::class) : function($parse, $token) use($id, $bp) {
      $expr= $this->expression($parse, $bp);
      return new UnaryExpression('prefix', $expr, $id, $token->line);
    };
  }

  public function suffix($id, $bp, $led= null) {
    $suffix= $this->symbol($id, $bp);
    $suffix->led= $led ? $led->bindTo($this, static::class) : function($parse, $token, $left) use($id) {
      $expr= new UnaryExpression('suffix', $left, $id, $token->line);
      return $expr;
    };
  }

  public function stmt($id, $func) {
    $stmt= $this->symbol($id);
    $stmt->std= $func->bindTo($this, static::class);
  }

  /**
   * Returns a single expression
   *
   * @param  lang.ast.Parse $parse
   * @param  int $rbp
   * @return lang.ast.Node
   */
  public function expression($parse, $rbp) {
    $t= $parse->token;
    $parse->forward();
    $left= $t->symbol->nud ? $t->symbol->nud->__invoke($parse, $t) : $t;

    while ($rbp < $parse->token->symbol->lbp) {
      $t= $parse->token;
      $parse->forward();
      $left= $t->symbol->led ? $t->symbol->led->__invoke($parse, $t, $left) : $t;
    }

    return $left;
  }

  /**
   * Returns a single statement
   *
   * @param  lang.ast.Parse $parse
   * @return lang.ast.Node
   */
  public function statement($parse) {
    if ($parse->token->symbol->std) {
      $t= $parse->token;
      $parse->forward();
      return $t->symbol->std->__invoke($parse, $t);
    }

    $expr= $this->expression($parse, 0);

    // Check for semicolon
    if (';' !== $parse->token->symbol->id) {
      $parse->raise('Missing semicolon after '.$expr->kind.' statement', null, $expr->line);
    } else {
      $parse->forward();
    }

    return $expr;
  }

  /**
   * Returns a list of statements
   *
   * @param  lang.ast.Parse $parse
   * @return lang.ast.Node[]
   */
  public function statements($parse) {
    $statements= [];
    while ('}' !== $parse->token->value) {
      if (null === ($statement= $this->statement($parse))) break;
      $statements[]= $statement;
    }
    return $statements;
  }

  /**
   * Returns extensions for this language. By convention, these are loaded
   * from a package with the same name as the class (but in lowercase).
   *
   * @return iterable
   */
  public function extensions() {
    yield new TransformationApi();
    foreach (Package::forName(strtr(strtolower(static::class), '\\', '.'))->getClasses() as $class) {
      if ($class->isSubclassOf(Extension::class)) yield $class->newInstance();
    }
  }

  /**
   * Returns a language with the given name
   *
   * @param  string $name
   * @return self
   */
  public static function named($name) {
    if (!isset(self::$instance[$name])) {
      self::$instance[$name]= Package::forName('lang.ast.syntax')->loadClass($name)->newInstance();
    }
    return self::$instance[$name];
  }
}