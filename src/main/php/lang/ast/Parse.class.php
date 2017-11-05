<?php namespace lang\ast;

use lang\ast\nodes\ClassValue;
use lang\ast\nodes\InterfaceValue;
use lang\ast\nodes\TraitValue;
use lang\ast\nodes\UseValue;
use lang\ast\nodes\CastValue;
use lang\ast\nodes\FunctionValue;
use lang\ast\nodes\ClosureValue;
use lang\ast\nodes\LambdaValue;
use lang\ast\nodes\MethodValue;
use lang\ast\nodes\ConstValue;
use lang\ast\nodes\PropertyValue;
use lang\ast\nodes\NewValue;
use lang\ast\nodes\NewClassValue;
use lang\ast\nodes\AssignmentValue;
use lang\ast\nodes\BinaryValue;
use lang\ast\nodes\UnaryValue;
use lang\ast\nodes\TernaryValue;
use lang\ast\nodes\OffsetValue;
use lang\ast\nodes\InstanceOfValue;
use lang\ast\nodes\InstanceValue;
use lang\ast\nodes\ScopeValue;
use lang\ast\nodes\InvokeValue;
use lang\ast\nodes\YieldValue;
use lang\ast\nodes\ForValue;
use lang\ast\nodes\ForeachValue;
use lang\ast\nodes\WhileValue;
use lang\ast\nodes\DoValue;
use lang\ast\nodes\IfValue;
use lang\ast\nodes\SwitchValue;
use lang\ast\nodes\CaseValue;
use lang\ast\nodes\TryValue;
use lang\ast\nodes\CatchValue;
use lang\ast\nodes\Signature;
use lang\ast\nodes\Parameter;

class Parse {
  private $tokens, $token, $scope;
  private $comment= null;
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
    $this->symbol('(literal)');
    $this->symbol('(variable)');

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
      if ('name' === $this->token->kind) {
        $node->value= new InstanceOfValue($left, $this->scope->resolve($this->token->value));
        $this->token= $this->advance();
      } else {
        $node->value= new InstanceOfValue($left, $this->expression(0));
      }

      $node->kind= 'instanceof';
      return $node;
    });

    $this->infix('->', 80, function($node, $left) {
      if ('{' === $this->token->value) {
        $this->token= $this->expect('{');
        $expr= $this->expression(0);
      } else {
        $expr= $this->token;
      }

      $node->value= new InstanceValue($left, $expr);
      $node->kind= 'instance';
      $this->token= $this->advance();
      return $node;
    });

    $this->infix('::', 80, function($node, $left) {
      $node->value= new ScopeValue($this->scope->resolve($left->value), $this->token);
      $node->kind= 'scope';
      $this->token= $this->advance();
      return $node;
    });

    $this->infix('==>', 80, function($node, $left) {
      $signature= new Signature([new Parameter($left->value, false, null, false, false, null, null)], null);
      $node->value= new LambdaValue($signature, $this->expression(0));
      $node->kind= 'lambda';
      return $node;
    });

    $this->infix('(', 80, function($node, $left) {
      $arguments= $this->arguments();
      $this->token= $this->expect(')');
      $node->value= new InvokeValue($left, $arguments);
      $node->kind= 'invoke';
      return $node;
    });

    $this->infix('[', 80, function($node, $left) {
      if (']' === $this->token->symbol->id) {
        $expr= null;
      } else {
        $expr= $this->expression(0);
      }
      $this->token= $this->expect(']');

      $node->value= new OffsetValue($left, $expr);
      $node->kind= 'offset';
      return $node;
    });

    $this->infix('{', 80, function($node, $left) {
      $expr= $this->expression(0);
      $this->token= $this->expect('}');

      $node->value= new OffsetValue($left, $expr);
      $node->kind= 'offset';
      return $node;
    });

    $this->infix('?', 80, function($node, $left) {
      $when= $this->expression(0);
      $this->token= $this->expect(':');
      $else= $this->expression(0);
      $node->value= new TernaryValue($left, $when, $else);
      $node->kind= 'ternary';
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

    // This is ambiguous:
    //
    // - An arrow function `($a) ==> $a + 1`
    // - An expression surrounded by parentheses `($a ?? $b)->invoke()`;
    // - A cast `(int)$a` or `(int)($a / 2)`.
    //
    // Resolve by looking ahead after the closing ")"
    $this->prefix('(', function($node) {
      static $types= [
        '<'        => true,
        '>'        => true,
        ','        => true,
        '?'        => true,
        ':'        => true
      ];

      $skipped= [$node, $this->token];
      $cast= true;
      $level= 1;
      while ($level > 0 && null !== $this->token->value) {
        if ('(' === $this->token->symbol->id) {
          $level++;
        } else if (')' === $this->token->symbol->id) {
          $level--;
        } else if ('name' !== $this->token->kind && !isset($types[$this->token->value])) {
          $cast= false;
        }
        $this->token= $this->advance();
        $skipped[]= $this->token;
      }
      $this->queue= array_merge($skipped, $this->queue);

      if (':' === $this->token->value || '==>' === $this->token->value) {
        $node->kind= 'lambda';

        $this->token= $this->advance();
        $signature= $this->signature();
        $this->token= $this->advance();
        $node->value= new LambdaValue($signature, $this->expression(0));
      } else if ($cast && '(' === $this->token->value || 'operator' !== $this->token->kind) {
        $node->kind= 'cast';

        $this->token= $this->advance();
        $this->token= $this->expect('(');
        $type= $this->type0(false);
        $this->token= $this->expect(')');
        $node->value= new CastValue($type, $this->expression(0));
      } else {
        $node->kind= 'braced';

        $this->token= $this->advance();
        $this->token= $this->expect('(');
        $node->value= $this->expression(0);
        $this->token= $this->expect(')');
      }
      return $node;
    });

    $this->prefix('[', function($node) {
      $values= [];
      while (']' !== $this->token->symbol->id) {
        $expr= $this->expression(0);

        if ('=>' === $this->token->symbol->id) {
          $this->token= $this->advance();
          $values[]= [$expr, $this->expression(0)];
        } else {
          $values[]= [null, $expr];
        }

        if (']' === $this->token->symbol->id) break;
        $this->token= $this->expect(',');
      }

      $this->token= $this->expect(']');
      $node->kind= 'array';
      $node->value= $values;
      return $node;
    });

    $this->prefix('{', function($node) {
      $node->kind= 'block';
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

      if ('variable' === $type->kind) {
        $node->value= new NewValue('$'.$type->value, $arguments);
        $node->kind= 'new';
      } else if ('class' === $type->value) {
        $node->value= new NewClassValue($this->clazz(null), $arguments);
        $node->kind= 'newclass';
      } else {
        $node->value= new NewValue($this->scope->resolve($type->value), $arguments);
        $node->kind= 'new';
      }
      return $node;
    });

    $this->prefix('yield', function($node) {
      if (';' === $this->token->symbol->id) {
        $node->kind= 'yield';
        $node->value= new YieldValue(null, null);
      } else if ('from' === $this->token->value) {
        $this->token= $this->advance();
        $node->kind= 'from';
        $node->value= $this->expression(0);
      } else {
        $node->kind= 'yield';
        $expr= $this->expression(0);
        if ('=>' === $this->token->symbol->id) {
          $this->token= $this->advance();
          $node->value= new YieldValue($expr, $this->expression(0));
        } else {
          $node->value= new YieldValue(null, $expr);
        }
      }
      return $node;
    });

    $this->prefix('...', function($node) {
      $node->kind= 'unpack';
      $node->value= $this->expression(0);
      return $node;
    });

    $this->prefix('function', function($node) {

      // Closure `$a= function() { ... };` vs. declaration `function a() { ... }`;
      // the latter explicitely becomes a statement by pushing a semicolon.
      if ('(' === $this->token->symbol->id) {
        $node->kind= 'closure';
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

        $node->value= new ClosureValue($signature, $use, $statements);
      } else {
        $node->kind= 'function';
        $name= $this->token->value;
        $this->token= $this->advance();
        $signature= $this->signature();

        if ('==>' === $this->token->value) {  // Compact syntax, terminated with ';'
          $n= new Node($this->token->symbol);
          $this->token= $this->advance();
          $n->value= $this->expression(0);
          $n->kind= 'return';
          $statements= [$n];
          $this->token= $this->expect(';');
        } else {                              // Regular function
          $this->token= $this->expect('{');
          $statements= $this->statements();
          $this->token= $this->expect('}');
        }

        $this->queue= [$this->token];
        $this->token= new Node($this->symbol(';'));
        $node->value= new FunctionValue($name, $signature, $statements);
      }

      return $node;
    });

    $this->prefix('static', function($node) {
      if ('variable' === $this->token->kind) {
        $node->kind= 'static';
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

    $this->stmt('<?', function($node) {
      $node->kind= 'start';
      $node->value= $this->token->value;

      $this->token= $this->advance();
      return $node;
    });

    $this->stmt('namespace', function($node) {
      $node->kind= 'package';
      $node->value= $this->token->value;

      $this->token= $this->advance();
      $this->token= $this->expect(';');

      $this->scope->package($node->value);
      return $node;
    });

    $this->stmt('use', function($node) {
      $import= $this->token->value;
      $this->token= $this->advance();

      if ('{' === $this->token->symbol->id) {
        $types= [];
        while ('}' !== $this->token->symbol->id) {
          $this->token= $this->advance();
          $class= $import.$this->token->value;
          $this->token= $this->advance();
          if ('as' === $this->token->value) {
            $this->token= $this->advance();
            $types[$class]= $this->token->value;
            $this->scope->import($this->token->value);
            $this->token= $this->advance();
          } else {
            $types[$class]= null;
            $this->scope->import($class);
          }
        }
        $this->token= $this->advance();
      } else if ('as' === $this->token->value) {
        $this->token= $this->advance();
        $types= [$import => $this->token->value];
        $this->scope->import($import, $this->token->value);
        $this->token= $this->advance();
      } else {
        $types= [$import => null];
        $this->scope->import($import);
      }

      $this->token= $this->expect(';');
      $node->kind= 'import';
      $node->value= $types;
      return $node;
    });

    $this->stmt('if', function($node) {
      $this->token= $this->expect('(');
      $condition= $this->expression(0);
      $this->token= $this->expect(')');

      $when= $this->block();

      if ('else' === $this->token->symbol->id) {
        $this->token= $this->advance();
        $otherwise= $this->block();
      } else {
        $otherwise= null;
      }

      $node->value= new IfValue($condition, $when, $otherwise);
      $node->kind= 'if';
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
          $cases[]= new CaseValue(null, []);
        } else if ('case' === $this->token->symbol->id) {
          $this->token= $this->advance();
          $expr= $this->expression(0);
          $this->token= $this->expect(':');
          $cases[]= new CaseValue($expr, []);
        } else {
          $cases[sizeof($cases) - 1]->body[]= $this->statement();
        }
      };
      $this->token= $this->expect('}');

      $node->value= new SwitchValue($condition, $cases);
      $node->kind= 'switch';
      return $node;
    });

    $this->stmt('break', function($node) {
      if (';' === $this->token->value) {
        $node->value= null;
        $this->token= $this->advance();
      } else {
        $node->value= $this->expression(0);
        $this->token= $this->expect(';');
      }

      $node->kind= 'break';
      return $node;
    });

    $this->stmt('continue', function($node) {
      if (';' === $this->token->value) {
        $node->value= null;
        $this->token= $this->advance();
      } else {
        $node->value= $this->expression(0);
        $this->token= $this->expect(';');
      }

      $node->kind= 'continue';
      return $node;
    });

    $this->stmt('do', function($node) {
      $block= $this->block();

      $this->token= $this->expect('while');
      $this->token= $this->expect('(');
      $expression= $this->expression(0);
      $this->token= $this->expect(')');
      $this->token= $this->expect(';');

      $node->value= new DoValue($expression, $block);
      $node->kind= 'do';
      return $node;
    });

    $this->stmt('while', function($node) {
      $this->token= $this->expect('(');
      $expression= $this->expression(0);
      $this->token= $this->expect(')');
      $block= $this->block();

      $node->value= new WhileValue($expression, $block);
      $node->kind= 'while';
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

      $block= $this->block();

      $node->value= new ForValue($init, $cond, $loop, $block);
      $node->kind= 'for';
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

      $block= $this->block();
      $node->value= new ForeachValue($expression, $key, $value, $block);
      $node->kind= 'foreach';
      return $node;
    });

    $this->stmt('throw', function($node) {
      $node->value= $this->expression(0);
      $node->kind= 'throw';
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
        while ('name' === $this->token->kind) {
          $types[]= $this->scope->resolve($this->token->value);
          $this->token= $this->advance();
          if ('|' !== $this->token->symbol->id) break;
          $this->token= $this->advance();
        }

        $variable= $this->token;
        $this->token= $this->advance();
        $this->token= $this->expect(')');

        $this->token= $this->expect('{');
        $catches[]= new CatchValue($types, $variable->value, $this->statements());
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

      $node->value= new TryValue($statements, $catches, $finally);
      $node->kind= 'try';
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
      $result->kind= 'return';
      return $result;
    });

    $this->stmt('abstract', function($node) {
      $this->token= $this->advance();
      $type= $this->scope->resolve($this->token->value);
      $this->token= $this->advance();

      $node->value= $this->clazz($type, ['abstract']);
      $node->kind= 'class';
      return $node;
    });

    $this->stmt('final', function($node) {
      $this->token= $this->advance();
      $type= $this->scope->resolve($this->token->value);
      $this->token= $this->advance();

      $node->value= $this->clazz($type, ['final']);
      $node->kind= 'class';
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
      $node->kind= 'annotation';
      return $node;
    });

    $this->stmt('class', function($node) {
      $type= $this->scope->resolve($this->token->value);
      $this->token= $this->advance();

      $node->value= $this->clazz($type);
      $node->kind= 'class';
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

      $node->value= new InterfaceValue($type, [], $parents, $body, $this->scope->annotations, $this->comment);
      $node->kind= 'interface';
      $this->scope->annotations= [];
      $this->comment= null;
      return $node;
    });

    $this->stmt('trait', function($node) {
      $type= $this->scope->resolve($this->token->value);
      $this->token= $this->advance();

      $this->token= $this->expect('{');
      $body= $this->body();
      $this->token= $this->expect('}');

      $node->value= new TraitValue($type, [], $body, $this->scope->annotations, $this->comment);
      $node->kind= 'trait';
      $this->scope->annotations= [];
      $this->comment= null;
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
    } else if ('name' === $this->token->kind) {
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
    $annotations= [];
    while (')' !== $this->token->symbol->id) {
      if ('<<' === $this->token->symbol->id) {
        do {
          $this->token= $this->advance();

          $annotation= [$this->token->value];
          $this->token= $this->advance();

          // Parameterized annotation
          if ('(' === $this->token->symbol->id) {
            $this->token= $this->expect('(');
            $annotation[]= $this->expression(0);
            $this->token= $this->expect(')');
          }

          $annotations[]= $annotation;
          if (',' === $this->token->symbol->id) {
            continue;
          } else if ('>>' === $this->token->symbol->id) {
            break;
          } else {
            $this->expect(', or >>');
          }
        } while (true);
        $this->token= $this->expect('>>');
      }

      if ('name' === $this->token->kind && isset($promotion[$this->token->value])) {
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
      $parameters[]= new Parameter($name, $byref, $type, $variadic, $promote, $default, $annotations);

      if (')' === $this->token->symbol->id) break;
      $this->token= $this->expect(',');
      $annotations= [];
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

    return new Signature($parameters, $return);
  }

   private function block() {
    if ('{'  === $this->token->symbol->id) {
      $this->token= $this->expect('{');
      $block= $this->statements();
      $this->token= $this->expect('}');
    } else {
      $block= [$this->statement()];
    }
    return $block;
  }

  private function clazz($name, $modifiers= []) {
    $comment= $this->comment;
    $this->comment= null;

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

    $return= new ClassValue($name, $modifiers, $parent, $implements, $body, $this->scope->annotations, $comment);
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
        $member->kind= 'use';

        $this->token= $this->advance();
        $types= [];
        do {
          $types[]= $this->scope->resolve($this->token->value);
          $this->token= $this->advance();
          if (',' === $this->token->value) {
            $this->token= $this->advance();
            continue;
          } else {
            break;
          }
        } while ($this->token->value);

        $aliases= [];
        if ('{' === $this->token->value) {
          $this->token= $this->advance();
          while ('}' !== $this->token->value) {
            $method= $this->token->value;
            $this->token= $this->advance();
            if ('::' === $this->token->value) {
              $this->token= $this->advance();
              $method= $this->scope->resolve($method).'::'.$this->token->value;
              $this->token= $this->advance();
            }
            $this->token= $this->expect('as');
            $alias= $this->token->value;
            $this->token= $this->advance();
            $this->token= $this->expect(';');
            $aliases[$method]= $alias;
          }
          $this->token= $this->expect('}');
        } else {
          $this->token= $this->expect(';');
        }

        $member->value= new UseValue($types, $aliases);
        $body[]= $member;
      } else if ('function' === $this->token->symbol->id) {
        $member= new Node($this->token->symbol);
        $member->kind= 'method';

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
          $n->kind= 'return';
          $statements= [$n];
          $this->token= $this->expect(';');
        } else {
          $this->token= $this->expect('{, ; or ==>');
        }

        $member->value= new MethodValue($name, $modifiers, $signature, $annotations, $statements, $this->comment);
        $body[$name.'()']= $member;
        $modifiers= [];
        $annotations= null;
        $this->comment= null;
      } else if ('const' === $this->token->symbol->id) {
        $n= new Node($this->token->symbol);
        $n->kind= 'const';
        $this->token= $this->advance();

        while (';' !== $this->token->symbol->id) {
          $member= clone $n;
          $name= $this->token->value;

          $this->token= $this->advance();
          $this->token= $this->expect('=');

          $member->value= new ConstValue($name, $modifiers, $this->expression(0));
          $body[$name]= $member;
          if (',' === $this->token->symbol->id) {
            $this->token= $this->expect(',');
          }
        }
        $this->token= $this->expect(';');
        $modifiers= [];
      } else if ('variable' === $this->token->kind) {
        $n= new Node($this->token->symbol);
        $n->kind= 'property';

        while (';' !== $this->token->symbol->id) {
          $member= clone $n;
          $name= $this->token->value;
          $this->token= $this->advance();

          if ('=' === $this->token->symbol->id) {
            $this->token= $this->expect('=');
            $member->value= new PropertyValue($name, $modifiers, $this->expression(0), $type, $annotations, $this->comment);
          } else {
            $member->value= new PropertyValue($name, $modifiers, null, $type, $annotations, $this->comment);
          }

          $body['$'.$name]= $member;
          if (',' === $this->token->symbol->id) {
            $this->token= $this->expect(',');
          }
        }
        $modifiers= [];
        $annotations= null;
        $this->comment= null;
        $type= null;
        $this->token= $this->expect(';');
      } else if ('<<' === $this->token->symbol->id) {
        do {
          $this->token= $this->advance();

          $annotation= [$this->token->value];
          $this->token= $this->advance();

          // Parameterized annotation
          if ('(' === $this->token->symbol->id) {
            $this->token= $this->expect('(');
            $annotation[]= $this->expression(0);
            $this->token= $this->expect(')');
          }

          $annotations[]= $annotation;
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
      $node->kind= 'literal';
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
      $result->kind= 'assignment';
      $result->value= new AssignmentValue($left, $id, $this->expression(9));
      return $result;
    };
    return $infix;
  }

  private function infix($id, $bp, $led= null) {
    $infix= $this->symbol($id, $bp);
    $infix->led= $led ?: function($node, $left) use($id, $bp) {
      $node->value= new BinaryValue($left, $id, $this->expression($bp));
      $node->kind= 'binary';
      return $node;
    };
    return $infix;
  }

  private function infixr($id, $bp, $led= null) {
    $infix= $this->symbol($id, $bp);
    $infix->led= $led ?: function($node, $left) use($id, $bp) {
      $node->value= new BinaryValue($left, $id, $this->expression($bp - 1));
      $node->kind= 'binary';
      return $node;
    };
    return $infix;
  }

  private function prefix($id, $nud= null) {
    $prefix= $this->symbol($id);
    $prefix->nud= $nud ?: function($node) use($id) {
      $node->value= new UnaryValue($this->expression(70), $id);
      $node->kind= 'unary';
      return $node;
    };
    return $prefix;
  }

  private function suffix($id, $bp, $led= null) {
    $suffix= $this->symbol($id, $bp);
    $suffix->led= $led ?: function($node, $left) use($id) {
      $node->value= new UnaryValue($left, $id);
      $node->kind= 'unary';
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

    while ($this->tokens->valid()) {
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
      } else if ('comment' === $type) {
        $this->comment= $value;
        continue;
      } else {
        throw new Error('Unexpected token '.$value.' on line '.$line);
      }

      $node->kind= $type;
      $node->value= $value;
      $node->line= $line;
      // \util\cmd\Console::writeLine('-> ', $node);
      return $node;
    }
    return new Node($this->symbol('(end)'));
  }

  public function execute() {
    $this->token= $this->advance();
    return $this->top();
  }
}