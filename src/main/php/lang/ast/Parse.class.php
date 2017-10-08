<?php namespace lang\ast;

class Parse {
  private $tokens, $token, $scope;
  private $symbols= [];
  private $queue= [];

  public function __construct($tokens, $scope= null) {
    $this->tokens= $tokens->getIterator();
    $this->scope= $scope ?: new Scope(null);
    $this->setup();
  }

  private function setup() {
    $this->symbol(':');
    $this->symbol(';');
    $this->symbol(',');
    $this->symbol(')');
    $this->symbol(']');
    $this->symbol('}');
    $this->symbol('else');

    $this->symbol('(end)');
    $this->symbol('(name)');
    $this->symbol('(literal)')->nud= function($node) { return $node; };    
    $this->symbol('(variable)')->nud= function($node) { return $node; };    

    $this->constant('true', true);
    $this->constant('false', false);
    $this->constant('null', null);

    $this->infix('==>', 10, function($node, $left) {
      $this->scope->define($left->value, $left);

      $this->token= $this->expect('{');
      $statements= $this->statements();
      $this->token= $this->expect('}');

      $node->value= [$left, $statements];
      $node->arity= 'closure';
      return $node;
    });

    $this->infix('->', 20, function($node, $left) {
      $expression= $this->expression(0);
      $node->value= [$left, $expression];
      $node->arity= 'instance';
      return $node;
    });


    $this->infix('::', 20, function($node, $left) {
      $expression= $this->expression(0);
      $node->value= [$this->scope->resolve($left->value), $expression];
      $node->arity= 'static';
      return $node;
    });

    $this->infixr('??', 30);
    $this->infixr('?:', 30);
    $this->infixr('&&', 30);
    $this->infixr('||', 30);

    $this->infixr('==', 40);
    $this->infixr('===', 40);
    $this->infixr('!=', 40);
    $this->infixr('!==', 40);
    $this->infixr('<', 40);
    $this->infixr('<=', 40);
    $this->infixr('>', 40);
    $this->infixr('>=', 40);
    $this->infixr('<=>', 40);

    $this->infix('+', 50);
    $this->infix('-', 50);
    $this->infix('&', 50);
    $this->infix('|', 50);

    $this->infix('*', 60);
    $this->infix('/', 60);
    $this->infix('.', 60);
    $this->infix('**', 60);

    $this->infixr('<<', 70);
    $this->infixr('>>', 70);

    $this->infix('(', 80, function($node, $left) {
      $arguments= $this->arguments();
      $this->token= $this->expect(')');
      $node->value= [$left, $arguments];
      $node->arity= 'invoke';
      return $node;
    });

    $this->infix('[', 80, function($node, $left) {
      $expr= $this->expression(0);
      $this->token= $this->expect(']');
      $node->value= [$left, $expr];
      $node->arity= 'offset';
      return $node;
    });

    $this->suffix('++', 50);
    $this->suffix('--', 50);

    $this->prefix('~');
    $this->prefix('!');
    $this->prefix('++');
    $this->prefix('--');

    $this->assignment('=');
    $this->assignment('&=');
    $this->assignment('|=');
    $this->assignment('+=');
    $this->assignment('-=');
    $this->assignment('*=');
    $this->assignment('/=');
    $this->assignment('.=');
    $this->assignment('**=');
    $this->assignment('>>=');
    $this->assignment('<<=');

    $this->prefix('(', function($node) {

      // Look ahead, resolving ambiguities
      $skipped= [$node, $this->token];
      while (')' !== $this->token->symbol->id) {
        $this->token= $this->advance();
        $skipped[]= $this->token;
      }
      $this->token= $this->advance();

      // (int)$i
      if (3 === sizeof($skipped) && 'name' === $skipped[1]->arity) {
        $node->arity= 'cast';
        $node->value= [$this->scope->resolve($skipped[1]->value), $this->expression(0)];
        return $node;
      }

      $skipped[]= $this->token;
      $this->queue= $skipped;

      // ($a) ==> $a + 1 vs. ($a ?? $b)->invoke();
      if ('==>' === $this->token->value) {
        $this->token= $this->advance();
        $node= $this->func(null, []);
        $node->arity= 'closure';
        $this->queue= [$this->token];
        $this->token= new Node($this->symbol(';'));
      } else {
        $node->arity= 'braced';
        $node->value= $this->expression(0);        
      }
      return $node;
    });

    $this->prefix('new', function($node) {
      $type= $this->token->value;
      $this->token= $this->advance();

      $this->token= $this->expect('(');
      $arguments= $this->arguments();
      $this->token= $this->expect(')');

      $node->arity= 'new';
      $node->value= [$this->scope->resolve($type), $arguments];
      return $node;
    });

    $this->prefix('...', function($node) {
      $node->arity= 'unpack';
      $node->value= $this->token;

      $this->token= $this->advance();
      return $node;
    });

    $this->prefix('function', function($node) {

      // Closure `$a= function() { ... };` vs. declaration `function a() { ... }`;
      // the latter explicitely becomes a statement by pushing a semicolon.
      if ('(' === $this->token->symbol->id) {
        $name= null;
      } else {
        $name= $this->token->value;
        $this->token= $this->advance();
      }

      $node= $this->func($name, []);
      $node->arity= 'function';

      $this->queue= $name ? [new Node($this->symbol(';')), $this->token] : [$this->token];
      $this->token= $this->advance();
      return $node;
    });

    $this->stmt('<?php', function($node) {
      $node->arity= 'start';
      return $node;
    });

    $this->stmt('namespace', function($node) {
      $package= $this->token->value;
      $this->token= $this->advance();
      $this->token= $this->expect(';');

      $this->scope->package($package);

      $node->arity= 'package';
      $node->value= $package;
      return $node;
    });

    $this->stmt('use', function($node) {
      $import= $this->token->value;
      $this->token= $this->advance();

      if ('{' === $this->token->symbol->id) {
        while ('}' !== $this->token->symbol->id) {
          $this->token= $this->advance();
          $class= $this->token->value;
          $this->scope->import($import.$class);
          $this->token= $this->advance();
        }
        $this->token= $this->advance();
      } else {
        $this->scope->import($import);
      }

      $this->token= $this->expect(';');
      $node->arity= 'import';
      return $node;
    });

    $this->stmt('if', function($node) {
      $this->token= $this->expect('(');
      $condition= $this->expression(0);
      $this->token= $this->expect(')');

      if ('{'  === $this->token->symbol->id) {
        $this->token= $this->expect('{');
        $when= $this->statements();
        $this->token= $this->expect('}');
      } else {
        $when= [$this->statement()];
      }

      if ('else' === $this->token->symbol->id) {
        $this->token= $this->advance('else');
        if ('{'  === $this->token->symbol->id) {
          $this->token= $this->expect('{');
          $otherwise= $this->statements();
          $this->token= $this->expect('}');
        } else {
          $otherwise= [$this->statement()];
        }
      } else {
        $otherwise= null;
      }

      $node->value= [$condition, $when, $otherwise];
      $node->arity= 'if';
      return $node;
    });

    $this->stmt('foreach', function($node) {
      $this->token= $this->expect('(');
      $expression= $this->expression(0);

      $this->token= $this->advance('as');
      $variable= $this->expression(0);
      $this->scope->define($variable->value, $variable);
      $this->token= $this->expect(')');

      $this->token= $this->expect('{');
      $statements= $this->statements();
      $this->token= $this->expect('}');

      $node->value= [$expression, $variable, $statements];
      $node->arity= 'foreach';
      return $node;
    });

    $this->stmt('throw', function($node) {
      $node->value= $this->expression(0);
      $node->arity= 'throw';
      $this->token= $this->expect(';');
      return $node;      
    });

    $this->stmt('try', function($node) {
      $this->token= $this->expect('{');
      $statements= $this->statements();
      $this->token= $this->expect('}');

      $catches= [];
      while ('catch'  === $this->token->symbol->id) {
        $this->token= $this->advance();
        $this->token= $this->expect('(');

        $types= [];
        while ('name' === $this->token->arity) {
          $types[]= $this->scope->resolve($this->token->value);
          $this->token= $this->advance();
          if ('|' !== $this->token->symbol->id) break;
          $this->token= $this->advance();
        }

        $variable= $this->token;
        $this->scope->define($variable->value, $variable);
  
        $this->token= $this->advance();
        $this->token= $this->expect(')');

        $this->token= $this->expect('{');
        $catches[]= [$types, $variable->value, $this->statements()];
        $this->token= $this->expect('}');
      }

      if ('finally' === $this->token->value) {
        $this->token= $this->advance();
        $this->token= $this->expect('{');
        $finally= $this->statements();
        $this->token= $this->expect('}');
      } else {
        $finally= null;
      }

      $node->value= [$statements, $catches, $finally];
      $node->arity= 'try';
      return $node;      
    });

    $this->stmt('return', function($node) {
      if (';' === $this->token->symbol->id) {
        $expr= null;
      } else {
        $expr= $this->expression(0);
      }
      $this->token= $this->expect(';');

      $result= new Node($node->symbol);
      $result->value= $expr;
      $result->arity= 'return';
      return $result;
    });

    $this->stmt('class', function($node) {
      static $modifier= [
        'private'   => true,
        'protected' => true,
        'public'    => true,
        'static'    => true,
        'final'     => true,
        'abstract'  => true
      ];
      $t= $this->token->value;

      $this->token= $this->advance();
      if ('extends' === $this->token->value) {
        $this->token= $this->advance();
        $parent= $this->token->value;
        $this->token= $this->advance();
      } else {
        $parent= null;
      }

      $this->token= $this->expect('{');
      $body= [];
      $modifiers= [];
      $annotations= null;
      $type= null;
      while ('}' !== $this->token->symbol->id) {
        if (isset($modifier[$this->token->symbol->id])) {
          $modifiers[]= $this->token->symbol->id;
          $this->token= $this->advance();
        } else if ('function' === $this->token->symbol->id) {
          $this->token= $this->advance();
          $name= $this->token->value;
          $this->token= $this->advance();
          $member= $this->func($name, $modifiers);
          $member->arity= 'method';
          $member->value[]= $annotations;
          $body[]= $member;
          $modifiers= [];
          $annotations= null;
        } else if ('name' === $this->token->arity) {
          $type= $this->scope->resolve($this->token->value);
          $this->token= $this->advance();
        } else if ('variable' === $this->token->arity) {
          while (';' !== $this->token->symbol->id) {
            $name= $this->token->value;
            $member= new Node($this->token->symbol);
            $member->arity= 'property';
            $this->token= $this->advance();

            if ('=' === $this->token->symbol->id) {
              $this->token= $this->expect('=');
              $member->value= [$name, $modifiers, $this->expression(0), $type, $annotations];
            } else {
              $member->value= [$name, $modifiers, null, $type, $annotations];
            }

            $body[]= $member;
            if (',' === $this->token->symbol->id) {
              $this->token= $this->expect(',');
            }
          }
          $modifiers= [];
          $annotations= null;
          $type= null;
          $this->token= $this->expect(';');
        } else if ('<<' === $this->token->symbol->id) {
          while ('>>' !== $this->token->symbol->id) {
            $this->token= $this->advance();
            $annotation= [$this->token->value];
            $this->token= $this->advance();

            if ('(' === $this->token->symbol->id) {
              $this->token= $this->expect('(');
              $annotation[]= $this->arguments();
              $this->token= $this->expect(')');
            }

            if (',' === $this->token->symbol->id) {
              $this->token= $this->expect(',');
            }
            $annotations[]= $annotation;
          }
          $this->token= $this->expect('>>');
        } else {
          $this->expect('property or method');
        }
      }
      $this->token= $this->expect('}');

      $node->value= [$t, $parent, $body];
      $node->arity= 'class';

      return $node;
    });
  }

  private function parameters() {
    static $promotion= ['private' => true, 'protected' => true, 'public' => true];

    $parameters= [];
    while (')' !== $this->token->symbol->id) {
      if (isset($promotion[$this->token->value])) {
        $promote= $this->token->value;
        $this->token= $this->advance();
      } else {
        $promote= null;
      }

      if ('name' === $this->token->arity) {
        $type= $this->scope->resolve($this->token->value);
        $this->token= $this->advance();
      } else {
        $type= null;
      }

      if ('...' === $this->token->value) {
        $variadic= true;
        $this->token= $this->advance();
      } else {
        $variadic= false;
      }

      $this->scope->define($this->token->value, $this->token);
      $parameters[]= [$this->token->value, $type, $variadic, $promote];
      $this->token= $this->advance();

      if (',' === $this->token->symbol->id) {
        $this->token= $this->expect(',');
      }
    }
    return $parameters;
  }

  private function func($name, $modifiers) {
    $node= new Node($this->token->symbol);

    $this->scope= new Scope($this->scope); {
      $this->token= $this->expect('(');
      $parameters= $this->parameters();
      $this->token= $this->expect(')');

      if (':' === $this->token->value) {
        $this->token= $this->advance();
        $return= $this->scope->resolve($this->token->value);
        $this->token= $this->advance();
      } else {
        $return= null;
      }

      if ('{' === $this->token->value) {
        $this->token= $this->advance();
        $statements= $this->statements();
        $this->token= $this->expect('}');
      } else if ('==>' === $this->token->value) {
        $n= new Node($this->token->symbol);
        $this->token= $this->advance();
        $n->value= $this->expression(0);
        $n->arity= 'return';
        $statements= [$n];
        $this->token= $this->expect(';');
      } else {
        $this->token= $this->expect('{ or ==>');
      }
    }

    $this->scope= $this->scope->parent;
    $node->value= [$name, $modifiers, $parameters, $statements, $return];
    return $node;
  }

  private function arguments() {
    $arguments= [];
    while (')' !== $this->token->symbol->id) {
      $arguments[]= $this->expression(0, false);    // Undefined arguments are OK
      if (',' === $this->token->symbol->id) {
        $this->token= $this->expect(',');
      }
    }
    return $arguments;
  }

  private function expression($rbp, $nud= true) {
    $t= $this->token;
    $this->token= $this->advance();
    if ($nud || $t->symbol->nud) {
      $left= $t->nud();
    } else {
      $left= $t;
    }

    while ($rbp < $this->token->symbol->lbp) {
      $t= $this->token;
      $this->token= $this->advance();
      $left= $t->led($left);
    }

    return $left;
  }

  private function top() {
    while ('(end)' !== $this->token->symbol->id) {
      if (null === ($statement= $this->statement())) break;
      yield $statement;
    }
  }

  private function statements() {
    $statements= [];
    while ('}' !== $this->token->symbol->id) {
      if (null === ($statement= $this->statement())) break;
      $statements[]= $statement;
    }
    return $statements;
  }

  private function statement() {
    if ($this->token->symbol->std) {
      $t= $this->token;
      $this->token= $this->advance();
      return $t->std();
    }

    $expr= $this->expression(0);
    $this->token= $this->expect(';');
    return $expr;
  }

  // {{ setup
  private function symbol($id, $lbp= 0) {
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

  private function constant($id, $value) {
    $const= $this->symbol($id);
    $const->nud= function($node) use($value) {
      $node->arity= 'literal';
      $node->value= $value;
      return $node;
    };
    return $const;
  }

  private function stmt($id, $func) {
    $stmt= $this->symbol($id);
    $stmt->std= $func;
    return $stmt;
  }

  private function assignment($id) {
    $infix= $this->symbol($id, 10);
    $infix->led= function($node, $left) use($id) {
      $result= new Node($this->symbol($id));
      $result->arity= 'assignment';
      $result->value= [$left, $this->expression(9)];
      return $result;
    };
    return $infix;
  }

  private function infix($id, $bp, $led= null) {
    $infix= $this->symbol($id, $bp);
    $infix->led= $led ?: function($node, $left) use($bp) {
      $node->value= [$left, $this->expression($bp)];
      $node->arity= 'binary';
      return $node;
    };
    return $infix;
  }

  private function infixr($id, $bp, $led= null) {
    $infix= $this->symbol($id, $bp);
    $infix->led= $led ?: function($node, $left) use($bp) {
      $node->value= [$left, $this->expression($bp - 1)];
      $node->arity= 'binary';
      return $node;
    };
    return $infix;
  }

  private function prefix($id, $nud= null) {
    $prefix= $this->symbol($id);
    $prefix->nud= $nud ?: function($node) {
      $node->value= $this->expression(70);
      $node->arity= 'unary';
      return $node;
    };
    return $prefix;
  }

  private function suffix($id, $bp, $led= null) {
    $suffix= $this->symbol($id, $bp);
    $suffix->led= $led ?: function($node, $left) {
      $node->value= $left;
      $node->arity= 'unary';
      return $node;
    };
    return $suffix;
  }
  // }}}

  private function expect($id) {
    if ($id !== $this->token->symbol->id) {
      throw new Error('Expected `'.$id.'`, have `'.$this->token->symbol->id.'`');
    }

    return $this->advance();
  }

  private function advance() {
    if ($this->queue) return array_shift($this->queue);

    if ($this->tokens->valid()) {
      $value= $this->tokens->current();
      $type= $this->tokens->key();
      $this->tokens->next();
      if ('name' === $type) {
        $node= $this->scope->find($value) ?: new Node($this->symbol($value) ?: clone $this->symbol('(name)'));
      } else if ('operator' === $type) {
        $node= new Node($this->symbol($value));
      } else if ('string' === $type || 'integer' === $type || 'decimal' === $type) {
        $node= new Node(clone $this->symbol('(literal)'));
        $type= 'literal';
      } else if ('variable' === $type) {
        $node= new Node(clone $this->symbol('(variable)'));
        $type= 'variable';
      } else {
        throw new Error('Unexpected token '.$value);
      }
      $node->arity= $type;
      $node->value= $value;
      // \util\cmd\Console::writeLine('-> ', $node);
      return $node;
    } else {
      return new Node($this->symbol('(end)'));
    }
  }

  public function execute() {
    $this->token= $this->advance();
    return $this->top();
  }
}