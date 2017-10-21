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

    $this->constant('true', 'true');
    $this->constant('false', 'false');
    $this->constant('null', 'null');

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
    $this->infix('^', 50);

    $this->infix('*', 60);
    $this->infix('/', 60);
    $this->infix('%', 60);
    $this->infix('.', 60);
    $this->infix('**', 60);

    $this->infixr('<<', 70);
    $this->infixr('>>', 70);

    $this->infix('instanceof', 60, function($node, $left) {
      if ('name' === $this->token->arity) {
        $node->value= [$left, $this->scope->resolve($this->token->value)];
        $this->token= $this->advance();
      } else {
        $node->value= [$left, $this->expression(0)];
      }

      $node->arity= 'instanceof';
      return $node;
    });

    $this->infix('->', 80, function($node, $left) {
      if ('{' === $this->token->value) {
        $this->token= $this->expect('{');
        $expr= $this->expression(0);
      } else {
        $expr= $this->token;
      }

      $node->value= [$left, $expr];
      $node->arity= 'instance';
      $this->token= $this->advance();
      return $node;
    });

    $this->infix('::', 80, function($node, $left) {
      $node->value= [$this->scope->resolve($left->value), $this->token];
      $node->arity= 'scope';
      $this->token= $this->advance();
      return $node;
    });

    $this->infix('(', 80, function($node, $left) {
      $arguments= $this->arguments();
      $this->token= $this->expect(')');
      $node->value= [$left, $arguments];
      $node->arity= 'invoke';
      return $node;
    });

    $this->infix('[', 80, function($node, $left) {
      if (']' === $this->token->symbol->id) {
        $expr= null;
      } else {
        $expr= $this->expression(0);
      }
      $this->token= $this->expect(']');

      $node->value= [$left, $expr];
      $node->arity= 'offset';
      return $node;
    });

    $this->infix('{', 80, function($node, $left) {
      $expr= $this->expression(0);
      $this->token= $this->expect('}');

      $node->value= [$left, $expr];
      $node->arity= 'offset';
      return $node;
    });

    $this->infix('?', 80, function($node, $left) {
      $when= $this->expression(0);
      $this->token= $this->expect(':');
      $else= $this->expression(0);
      $node->value= [$left, $when, $else];
      $node->arity= 'ternary';
      return $node;
    });

    $this->suffix('++', 50);
    $this->suffix('--', 50);

    $this->prefix('@');
    $this->prefix('&');
    $this->prefix('~');
    $this->prefix('!');
    $this->prefix('+');
    $this->prefix('-');
    $this->prefix('++');
    $this->prefix('--');
    $this->prefix('clone');

    $this->assignment('=');
    $this->assignment('&=');
    $this->assignment('|=');
    $this->assignment('^=');
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
      if (':' ===  $this->token->value || '==>' === $this->token->value) {
        $node->arity= 'lambda';

        $this->token= $this->advance();
        $signature= $this->signature();
        $this->token= $this->advance();
        $node->value= [$signature, $this->expression(0)];
      } else {
        $node->arity= 'braced';

        $this->token= $this->advance();
        $this->token= $this->expect('(');
        $node->value= $this->expression(0);
        $this->token= $this->expect(')');
      }
      return $node;
    });

    $this->prefix('[', function($node) {
      $i= 0;
      $values= [];
      while (']' !== $this->token->symbol->id) {
        $expr= $this->expression(0);

        if ('=>' === $this->token->symbol->id) {
          $this->token= $this->advance();
          $values[$expr->value]= $this->expression(0);
        } else {
          $values[$i++]= $expr;
        }

        if (']' === $this->token->symbol->id) break;
        $this->token= $this->expect(',');
      }

      $this->token= $this->expect(']');
      $node->arity= 'array';
      $node->value= $values;
      return $node;
    });

    $this->prefix('{', function($node) {
      $node->arity= 'block';
      $node->value= $this->statements();
      $this->token= new Node($this->symbol(';'));
      return $node;
    });

    $this->prefix('new', function($node) {
      $type= $this->token;
      $this->token= $this->advance();

      $this->token= $this->expect('(');
      $arguments= $this->arguments();
      $this->token= $this->expect(')');

      // Anonymous classes
      $node->arity= 'new';
      if ('variable' === $type->arity) {
        $node->value= ['$'.$type->value, $arguments];
      } else if ('class' === $type->value) {
        $node->value= [null, $arguments, $this->clazz(null)];
      } else {
        $node->value= [$this->scope->resolve($type->value), $arguments];
      }
      return $node;
    });

    $this->prefix('yield', function($node) {
      if (';' === $this->token->symbol->id) {
        $node->arity= 'yield';
        $node->value= [null, null];
      } else if ('from' === $this->token->value) {
        $this->token= $this->advance();
        $node->arity= 'from';
        $node->value= $this->expression(0);
      } else {
        $node->arity= 'yield';
        $expr= $this->expression(0);
        if ('=>' === $this->token->symbol->id) {
          $this->token= $this->advance();
          $node->value= [$expr, $this->expression(0)];
        } else {
          $node->value= [null, $expr];
        }
      }
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
        $node->arity= 'closure';
        $signature= $this->signature();

        if ('use' === $this->token->value) {
          $this->token= $this->advance();
          $this->token= $this->advance();
          $use= [];
          while (')' !== $this->token->symbol->id) {
            if ('&' === $this->token->value) {
              $this->token= $this->advance();
              $use[]= '&$'.$this->token->value;
            } else {
              $use[]= '$'.$this->token->value;
            }
            $this->token= $this->advance();
            if (')' === $this->token->symbol->id) break;
            $this->token= $this->expect(',');
          }
          $this->token= $this->expect(')');
        } else {
          $use= null;
        }

        $this->token= $this->expect('{');
        $statements= $this->statements();
        $this->token= $this->expect('}');

        $node->value= [$signature, $use, $statements];
      } else {
        $node->arity= 'function';
        $name= $this->token->value;
        $this->token= $this->advance();
        $signature= $this->signature();

        if ('==>' === $this->token->value) {  // Compact syntax, terminated with ';'
          $n= new Node($this->token->symbol);
          $this->token= $this->advance();
          $n->value= $this->expression(0);
          $n->arity= 'return';
          $statements= [$n];
          $this->token= $this->expect(';');
        } else {                              // Regular function
          $this->token= $this->expect('{');
          $statements= $this->statements();
          $this->token= $this->expect('}');
        }

        $this->queue= [$this->token];
        $this->token= new Node($this->symbol(';'));
        $node->value= [$name, $signature, $statements];
      }

      return $node;
    });

    $this->prefix('static', function($node) {
      if ('variable' === $this->token->arity) {
        $node->arity= 'static';
        $node->value= [];
        while (';' !== $this->token->symbol->id) {
          $variable= $this->token->value;
          $this->token= $this->advance();

          if ('=' === $this->token->symbol->id) {
            $this->token= $this->expect('=');
            $initial= $this->expression(0);
          } else {
            $initial= null;
          }

          $node->value[$variable]= $initial;
          if (',' === $this->token->symbol->id) {
            $this->token= $this->expect(',');
          }
        }
      }
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

    $this->stmt('switch', function($node) {
      $this->token= $this->expect('(');
      $condition= $this->expression(0);
      $this->token= $this->expect(')');

      $cases= [];
      $this->token= $this->expect('{');
      while ('}' !== $this->token->symbol->id) {
        if ('default' === $this->token->symbol->id) {
          $this->token= $this->advance();
          $this->token= $this->expect(':');
          $cases[]= [null, []];
        } else if ('case' === $this->token->symbol->id) {
          $this->token= $this->advance();
          $expr= $this->expression(0);
          $this->token= $this->expect(':');
          $cases[]= [$expr, []];
        } else {
          $cases[sizeof($cases) - 1][1][]= $this->statement();
        }
      };
      $this->token= $this->expect('}');

      $node->value= [$condition, $cases];
      $node->arity= 'switch';
      return $node;
    });

    $this->stmt('break', function($node) {
      if (';' === $this->token->value) {
        $expr= null;
        $this->token= $this->advance();
      } else {
        $expr= $this->expression(0);
        $this->token= $this->expect(';');
      }

      $node->value= $expr;
      $node->arity= 'break';
      return $node;
    });

    $this->stmt('continue', function($node) {
      if (';' === $this->token->value) {
        $expr= null;
        $this->token= $this->advance();
      } else {
        $expr= $this->expression(0);
        $this->token= $this->expect(';');
      }

      $node->value= $expr;
      $node->arity= 'continue';
      return $node;
    });

    $this->stmt('do', function($node) {
      $loop= $this->statement();

      $this->token= $this->expect('while');
      $this->token= $this->expect('(');
      $expression= $this->expression(0);
      $this->token= $this->expect(')');
      $this->token= $this->expect(';');

      $node->value= [$expression, $loop];
      $node->arity= 'do';
      return $node;
    });

    $this->stmt('while', function($node) {
      $this->token= $this->expect('(');
      $expression= $this->expression(0);
      $this->token= $this->expect(')');
      $loop= $this->statement();

      $node->value= [$expression, $loop];
      $node->arity= 'while';
      return $node;
    });

    $this->stmt('for', function($node) {
      $this->token= $this->expect('(');
      $init= $this->arguments(';');
      $this->token= $this->advance(';');
      $cond= $this->arguments(';');
      $this->token= $this->advance(';');
      $loop= $this->arguments(')');
      $this->token= $this->advance(')');

      $stmt= $this->statement();

      $node->value= [$init, $cond, $loop, $stmt];
      $node->arity= 'for';
      return $node;
    });

    $this->stmt('foreach', function($node) {
      $this->token= $this->expect('(');
      $expression= $this->expression(0);

      $this->token= $this->advance('as');
      $expr= $this->expression(0);

      if ('=>' === $this->token->symbol->id) {
        $this->token= $this->advance();
        $key= $expr;
        $value= $this->expression(0);
      } else {
        $key= null;
        $value= $expr;
      }

      $this->token= $this->expect(')');

      $loop= $this->statement();
      $node->value= [$expression, $key, $value, $loop];
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

    $this->stmt('abstract', function($node) {
      $this->token= $this->advance();
      $type= $this->scope->resolve($this->token->value);
      $this->token= $this->advance();

      $node->value= $this->clazz($type, ['abstract']);
      $node->arity= 'class';
      return $node;
    });

    $this->stmt('final', function($node) {
      $this->token= $this->advance();
      $type= $this->scope->resolve($this->token->value);
      $this->token= $this->advance();

      $node->value= $this->clazz($type, ['final']);
      $node->arity= 'class';
      return $node;
    });

    $this->stmt('<<', function($node) {
      do {
        $annotation= [$this->token->value];
        $this->token= $this->advance();

        if ('(' === $this->token->symbol->id) {
          $this->token= $this->expect('(');
          $annotation[]= $this->expression(0);
          $this->token= $this->expect(')');
        }

        $this->scope->annotations[]= $annotation;
        if (',' === $this->token->symbol->id) {
          continue;
        } else if ('>>' === $this->token->symbol->id) {
          break;
        } else {
          $this->expect(', or >>');
        }
      } while (true);

      $this->token= $this->expect('>>');
      $node->arity= 'annotation';
      return $node;
    });

    $this->stmt('class', function($node) {
      $type= $this->scope->resolve($this->token->value);
      $this->token= $this->advance();

      $node->value= $this->clazz($type);
      $node->arity= 'class';
      return $node;
    });

    $this->stmt('interface', function($node) {
      $type= $this->scope->resolve($this->token->value);
      $this->token= $this->advance();

      $parents= [];
      if ('extends' === $this->token->value) {
        $this->token= $this->advance();
        do {
          $parents[]= $this->scope->resolve($this->token->value);
          $this->token= $this->advance();
          if (',' === $this->token->symbol->id) {
            $this->token= $this->expect(',');
          } else if ('{' === $this->token->symbol->id) {
            break;
          } else {
            $this->expect(', or {');
          }
        } while (true);
      }

      $this->token= $this->expect('{');
      $body= $this->body();
      $this->token= $this->expect('}');

      $node->value= [$type, [], $parents, $body, $this->scope->annotations];
      $node->arity= 'interface';
      $this->scope->annotations= [];
      return $node;
    });

    $this->stmt('trait', function($node) {
      $type= $this->scope->resolve($this->token->value);
      $this->token= $this->advance();

      $this->token= $this->expect('{');
      $body= $this->body();
      $this->token= $this->expect('}');

      $node->value= [$type, [], $body, $this->scope->annotations];
      $node->arity= 'trait';
      $this->scope->annotations= [];
      return $node;
    });
  }

  private function type($optional= true) {
    $t= [];
    do {
      $t[]= $this->type0($optional);
      if ('|' === $this->token->symbol->id) {
        $this->token= $this->advance();
        continue;
      }
      return 1 === sizeof($t) ? $t[0] : new UnionType($t);
    } while (true);
  }

  private function type0($optional) {
    if ('?' === $this->token->symbol->id) {
      $this->token= $this->advance();
      $type= '?'.$this->scope->resolve($this->token->value);
      $this->token= $this->advance();
    } else if ('(' === $this->token->symbol->id) {
      $this->token= $this->advance();
      $type= $this->type(false);
      $this->token= $this->advance();
      return $type;
    } else if ('function' === $this->token->value) {
      $this->token= $this->advance();
      $this->token= $this->expect('(');
      $signature= [];
      if (')' !== $this->token->symbol->id) do {
        $signature[]= $this->type(false);
        if (',' === $this->token->symbol->id) {
          $this->token= $this->advance();
        } else if (')' === $this->token->symbol->id) {
          break;
        } else {
          $this->expect(', or )');
        }
      } while (true);
      $this->token= $this->expect(')');
      $this->token= $this->expect(':');
      return new FunctionType($signature, $this->type(false));
    } else if ('name' === $this->token->arity) {
      $type= $this->scope->resolve($this->token->value);
      $this->token= $this->advance();
    } else if ($optional) {
      return null;
    } else {
      $this->expect('type name');
    }

    if ('<' === $this->token->symbol->id) {
      $this->token= $this->advance();
      $components= [];
      do {
        $components[]= $this->type(false);
        if (',' === $this->token->symbol->id) {
          $this->token= $this->advance();
        } else if ('>' === $this->token->symbol->id) {
          break;
        } else if ('>>' === $this->token->symbol->id) {
          $this->queue[]= $this->token= new Node($this->symbol('>'));
          break;
        }
      } while (true);
      $this->token= $this->expect('>');

      if ('array' === $type) {
        return 1 === sizeof($components) ? new ArrayType($components[0]) : new MapType($components[0], $components[1]);
      } else {
        return new GenericType($type, $components);
      }
    } else {
      return new Type($type);
    }
  }

  private function parameters() {
    static $promotion= ['private' => true, 'protected' => true, 'public' => true];

    $parameters= [];
    while (')' !== $this->token->symbol->id) {
      if ('name' === $this->token->arity && isset($promotion[$this->token->value])) {
        $promote= $this->token->value;
        $this->token= $this->advance();
      } else {
        $promote= null;
      }

      $type= $this->type();

      if ('...' === $this->token->value) {
        $variadic= true;
        $this->token= $this->advance();
      } else {
        $variadic= false;
      }

      if ('&' === $this->token->value) {
        $byref= true;
        $this->token= $this->advance();
      } else {
        $byref= false;
      }

      $name= $this->token->value;
      $this->token= $this->advance();

      $default= null;
      if ('=' === $this->token->symbol->id) {
        $this->token= $this->advance();
        $default= $this->expression(0);
      }
      $parameters[]= [$name, $byref, $type, $variadic, $promote, $default];

      if (')' === $this->token->symbol->id) break;
      $this->token= $this->expect(',');
    }
    return $parameters;
  }

  private function signature() {
    $this->token= $this->expect('(');
    $parameters= $this->parameters();
    $this->token= $this->expect(')');

    if (':' === $this->token->value) {
      $this->token= $this->advance();
      $return= $this->type();
    } else {
      $return= null;
    }

    return [$parameters, $return];
  }

  private function clazz($name, $modifiers= []) {
    $parent= null;
    if ('extends' === $this->token->value) {
      $this->token= $this->advance();
      $parent= $this->scope->resolve($this->token->value);
      $this->token= $this->advance();
    }

    $implements= [];
    if ('implements' === $this->token->value) {
      $this->token= $this->advance();
      do {
        $implements[]= $this->scope->resolve($this->token->value);
        $this->token= $this->advance();
        if (',' === $this->token->symbol->id) {
          $this->token= $this->expect(',');
        } else if ('{' === $this->token->symbol->id) {
          break;
        } else {
          $this->expect(', or {');
        }
      } while (true);
    }

    $this->token= $this->expect('{');
    $body= $this->body();
    $this->token= $this->expect('}');

    $return= [$name, $modifiers, $parent, $implements, $body, $this->scope->annotations];
    $this->scope->annotations= [];
    return $return;
  }

  private function arguments($end= ')') {
    $arguments= [];
    while ($end !== $this->token->symbol->id) {
      $arguments[]= $this->expression(0, false);    // Undefined arguments are OK
      if (',' === $this->token->symbol->id) {
        $this->token= $this->expect(',');
      } else if ($end === $this->token->symbol->id) {
        break;
      } else {
        $this->expect($end.' or ,');
      }
    }
    return $arguments;
  }

  private function body() {
    static $modifier= [
      'private'   => true,
      'protected' => true,
      'public'    => true,
      'static'    => true,
      'final'     => true,
      'abstract'  => true
    ];

    $body= [];
    $modifiers= [];
    $annotations= null;
    $type= null;
    while ('}' !== $this->token->symbol->id) {
      if (isset($modifier[$this->token->symbol->id])) {
        $modifiers[]= $this->token->symbol->id;
        $this->token= $this->advance();
      } else if ('use' === $this->token->symbol->id) {
        $member= new Node($this->token->symbol);
        $member->arity= 'use';
        $this->token= $this->advance();
        $member->value= $this->scope->resolve($this->token->value);
        $body[]= $member;
        $this->token= $this->advance();
        $this->token= $this->expect(';');
      } else if ('function' === $this->token->symbol->id) {
        $member= new Node($this->token->symbol);
        $member->arity= 'method';

        $this->token= $this->advance();
        $name= $this->token->value;
        $this->token= $this->advance();
        $signature= $this->signature();

        if ('{' === $this->token->value) {          // Regular body
          $this->token= $this->advance();
          $statements= $this->statements();
          $this->token= $this->expect('}');
        } else if (';' === $this->token->value) {   // Abstract or interface method
          $statements= null;
          $this->token= $this->expect(';');
        } else if ('==>' === $this->token->value) { // Compact syntax, terminated with ';'
          $n= new Node($this->token->symbol);
          $this->token= $this->advance();
          $n->value= $this->expression(0);
          $n->arity= 'return';
          $statements= [$n];
          $this->token= $this->expect(';');
        } else {
          $this->token= $this->expect('{, ; or ==>');
        }

        $member->value= [$name, $modifiers, $signature, $annotations, $statements];
        $body[]= $member;
        $modifiers= [];
        $annotations= null;
      } else if ('const' === $this->token->symbol->id) {
        $n= new Node($this->token->symbol);
        $n->arity= 'const';
        $this->token= $this->advance();

        while (';' !== $this->token->symbol->id) {
          $member= clone $n;
          $name= $this->token->value;

          $this->token= $this->advance();
          $this->token= $this->expect('=');

          $member->value= [$name, $modifiers, $this->expression(0)];
          $body[]= $member;
          if (',' === $this->token->symbol->id) {
            $this->token= $this->expect(',');
          }
        }
        $this->token= $this->expect(';');
        $modifiers= [];
      } else if ('variable' === $this->token->arity) {
        $n= new Node($this->token->symbol);
        $n->arity= 'property';

        while (';' !== $this->token->symbol->id) {
          $member= clone $n;
          $name= $this->token->value;
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
        do {
          $this->token= $this->advance();

          // `$param: inject` vs. `inject`
          if ('variable' === $this->token->arity) {
            $target= &$annotations['param'][$this->token->value];
            $this->token= $this->advance();
            $this->token= $this->expect(':');
          } else {
            $target= &$annotations['member'];
          }

          $annotation= [$this->token->value];
          $this->token= $this->advance();

          // Parameterized annotation
          if ('(' === $this->token->symbol->id) {
            $this->token= $this->expect('(');
            $annotation[]= $this->expression(0);
            $this->token= $this->expect(')');
          }

          $target[]= $annotation;
          if (',' === $this->token->symbol->id) {
            continue;
          } else if ('>>' === $this->token->symbol->id) {
            break;
          } else {
            $this->expect(', or >>');
          }
        } while (true);
        $this->token= $this->expect('>>');
      } else if ($type= $this->type()) {
        continue;
      } else {
        $this->expect('a type, modifier, property, annotation or method');
      }
    }
    return $body;
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
      throw new Error('Expected `'.$id.'`, have `'.$this->token->symbol->id.'` on line '.$this->token->line);
    }

    return $this->advance();
  }

  private function advance() {
    if ($this->queue) return array_shift($this->queue);

    if ($this->tokens->valid()) {
      $type= $this->tokens->key();
      list($value, $line)= $this->tokens->current();
      $this->tokens->next();
      if ('name' === $type) {
        $node= new Node($this->symbol($value) ?: clone $this->symbol('(name)'));
      } else if ('operator' === $type) {
        $node= new Node($this->symbol($value));
      } else if ('string' === $type || 'integer' === $type || 'decimal' === $type) {
        $node= new Node(clone $this->symbol('(literal)'));
        $type= 'literal';
      } else if ('variable' === $type) {
        $node= new Node(clone $this->symbol('(variable)'));
        $type= 'variable';
      } else {
        throw new Error('Unexpected token '.$value.' on line '.$line);
      }
      $node->arity= $type;
      $node->value= $value;
      $node->line= $line;
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