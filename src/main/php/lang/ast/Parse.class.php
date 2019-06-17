<?php namespace lang\ast;

use lang\ast\nodes\Assignment;
use lang\ast\nodes\BinaryExpression;
use lang\ast\nodes\CaseLabel;
use lang\ast\nodes\CastExpression;
use lang\ast\nodes\CatchStatement;
use lang\ast\nodes\ClassDeclaration;
use lang\ast\nodes\ClosureExpression;
use lang\ast\nodes\Constant;
use lang\ast\nodes\DoLoop;
use lang\ast\nodes\ForLoop;
use lang\ast\nodes\ForeachLoop;
use lang\ast\nodes\FunctionDeclaration;
use lang\ast\nodes\IfStatement;
use lang\ast\nodes\InstanceExpression;
use lang\ast\nodes\InstanceOfExpression;
use lang\ast\nodes\InterfaceDeclaration;
use lang\ast\nodes\InvokeExpression;
use lang\ast\nodes\LambdaExpression;
use lang\ast\nodes\Method;
use lang\ast\nodes\NewClassExpression;
use lang\ast\nodes\NewExpression;
use lang\ast\nodes\NullSafeInstanceExpression;
use lang\ast\nodes\OffsetExpression;
use lang\ast\nodes\Parameter;
use lang\ast\nodes\Property;
use lang\ast\nodes\ScopeExpression;
use lang\ast\nodes\Signature;
use lang\ast\nodes\SwitchStatement;
use lang\ast\nodes\TernaryExpression;
use lang\ast\nodes\TraitDeclaration;
use lang\ast\nodes\TryStatement;
use lang\ast\nodes\UnaryExpression;
use lang\ast\nodes\UseExpression;
use lang\ast\nodes\UsingStatement;
use lang\ast\nodes\WhileLoop;
use lang\ast\nodes\YieldExpression;

class Parse {
  private static $symbols= [];

  private $tokens, $file, $token, $scope;
  private $comment= null;
  private $body= [];
  private $queue= [];
  private $errors= [];

  static function __static() {
    self::symbol(':');
    self::symbol(';');
    self::symbol(',');
    self::symbol(')');
    self::symbol(']');
    self::symbol('}');
    self::symbol('as');
    self::symbol('const');
    self::symbol('else');
    self::symbol('(end)');
    self::symbol('(name)');
    self::symbol('(literal)');
    self::symbol('(variable)');

    self::constant('true', 'true');
    self::constant('false', 'false');
    self::constant('null', 'null');
  }

  /**
   * Creates a new parse instance
   *
   * @param  lang.ast.Tokens $tokens
   * @param  string $file
   * @param  lang.ast.Scope $scope
   */
  public function __construct($tokens, $file= null, $scope= null) {
    $this->tokens= $tokens->getIterator();
    $this->scope= $scope ?: new Scope(null);
    $this->file= $file;

    // Setup parse rules
    $this->infixt('??', 30);
    $this->infixt('?:', 30);

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
        $node->value= new InstanceOfExpression($left, $this->scope->resolve($this->token->value));
        $this->token= $this->advance();
      } else {
        $node->value= new InstanceOfExpression($left, $this->expression(0));
      }

      $node->kind= 'instanceof';
      return $node;
    });

    $this->infix('->', 80, function($node, $left) {
      if ('{' === $this->token->value) {
        $this->token= $this->advance();
        $expr= $this->expression(0);
        $this->token= $this->next('}');
      } else {
        $expr= $this->token;
        $this->token= $this->advance();
      }

      $node->value= new InstanceExpression($left, $expr);
      $node->kind= 'instance';
      return $node;
    });

    $this->infix('?->', 80, function($node, $left) {
      if ('{' === $this->token->value) {
        $this->token= $this->advance();
        $expr= $this->expression(0);
        $this->token= $this->next('}');
      } else {
        $expr= $this->token;
        $this->token= $this->advance();
      }

      $node->value= new InstanceExpression($left, $expr);
      $node->kind= 'nullsafeinstance';
      return $node;
    });

    $this->infix('::', 80, function($node, $left) {
      $node->value= new ScopeExpression($this->scope->resolve($left->value), $this->token);
      $node->kind= 'scope';
      $this->token= $this->advance();
      return $node;
    });

    $this->infix('==>', 80, function($node, $left) {
      $this->warn('Hack language style arrow functions are deprecated, please use `fn` syntax instead');

      $signature= new Signature([new Parameter($left->value, null)], null);
      if ('{' === $this->token->value) {
        $this->token= $this->advance();
        $statements= $this->statements();
        $this->token= $this->next('}');
      } else {
        $statements= $this->expressionWithThrows(0);
      }

      $node->value= new LambdaExpression($signature, $statements);
      $node->kind= 'lambda';
      return $node;
    });

    $this->infix('(', 80, function($node, $left) {
      $arguments= $this->arguments();
      $this->token= $this->next(')');
      $node->value= new InvokeExpression($left, $arguments);
      $node->kind= 'invoke';
      return $node;
    });

    $this->infix('[', 80, function($node, $left) {
      if (']' === $this->token->value) {
        $expr= null;
        $this->token= $this->advance();
      } else {
        $expr= $this->expression(0);
        $this->token= $this->next(']');
      }

      $node->value= new OffsetExpression($left, $expr);
      $node->kind= 'offset';
      return $node;
    });

    $this->infix('{', 80, function($node, $left) {
      $expr= $this->expression(0);
      $this->token= $this->next('}');

      $node->value= new OffsetExpression($left, $expr);
      $node->kind= 'offset';
      return $node;
    });

    $this->infix('?', 80, function($node, $left) {
      $when= $this->expressionWithThrows(0);
      $this->token= $this->next(':');
      $else= $this->expressionWithThrows(0);
      $node->value= new TernaryExpression($left, $when, $else);
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
    $this->assignment('??=');

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
        if ('(' === $this->token->value) {
          $level++;
        } else if (')' === $this->token->value) {
          $level--;
        } else if ('name' !== $this->token->kind && !isset($types[$this->token->value])) {
          $cast= false;
        }
        $this->token= $this->advance();
        $skipped[]= $this->token;
      }
      $this->queue= array_merge($skipped, $this->queue);

      if (':' === $this->token->value || '==>' === $this->token->value) {
        $this->warn('Hack language style arrow functions are deprecated, please use `fn` syntax instead');

        $node->kind= 'lambda';
        $this->token= $this->advance();
        $signature= $this->signature();
        $this->token= $this->advance();

        if ('{' === $this->token->value) {
          $this->token= $this->advance();
          $statements= $this->statements();
          $this->token= $this->next('}');
        } else {
          $statements= $this->expressionWithThrows(0);
        }

        $node->value= new LambdaExpression($signature, $statements);
      } else if ($cast && ('operator' !== $this->token->kind || '(' === $this->token->value || '[' === $this->token->value)) {
        $node->kind= 'cast';

        $this->token= $this->advance();
        $this->token= $this->expect('(');
        $type= $this->type0(false);
        $this->token= $this->expect(')');
        $node->value= new CastExpression($type, $this->expression(0));
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
      while (']' !== $this->token->value) {
        $expr= $this->expression(0);

        if ('=>' === $this->token->value) {
          $this->token= $this->advance();
          $values[]= [$expr, $this->expression(0)];
        } else {
          $values[]= [null, $expr];
        }

        if (']' === $this->token->value) {
          break;
        } else {
          $this->token= $this->next(',', 'array literal');
        }
      }

      $this->token= $this->next(']', 'array literal');
      $node->kind= 'array';
      $node->value= $values;
      return $node;
    });

    $this->prefix('new', function($node) {
      $type= $this->token;
      $this->token= $this->advance();

      $this->token= $this->expect('(');
      $arguments= $this->arguments();
      $this->token= $this->expect(')');

      if ('variable' === $type->kind) {
        $node->value= new NewExpression('$'.$type->value, $arguments);
        $node->kind= 'new';
      } else if ('class' === $type->value) {
        $node->value= new NewClassExpression($this->clazz(null), $arguments);
        $node->kind= 'newclass';
      } else {
        $node->value= new NewExpression($this->scope->resolve($type->value), $arguments);
        $node->kind= 'new';
      }
      return $node;
    });

    $this->prefix('yield', function($node) {
      if (';' === $this->token->value) {
        $node->kind= 'yield';
        $node->value= new YieldExpression(null, null);
      } else if ('from' === $this->token->value) {
        $this->token= $this->advance();
        $node->kind= 'from';
        $node->value= $this->expression(0);
      } else {
        $node->kind= 'yield';
        $expr= $this->expression(0);
        if ('=>' === $this->token->value) {
          $this->token= $this->advance();
          $node->value= new YieldExpression($expr, $this->expression(0));
        } else {
          $node->value= new YieldExpression(null, $expr);
        }
      }
      return $node;
    });

    $this->prefix('...', function($node) {
      $node->kind= 'unpack';
      $node->value= $this->expression(0);
      return $node;
    });

    $this->prefix('fn', function($node) {
      $signature= $this->signature();

      $this->token= $this->expect('=>');

      if ('{' === $this->token->value) {
        $this->token= $this->expect('{');
        $statements= $this->statements();
        $this->token= $this->expect('}');
      } else {
        $statements= $this->expressionWithThrows(0);
      }

      $node->value= new LambdaExpression($signature, $statements);
      $node->kind= 'lambda';
      return $node;
    });

    $this->prefix('function', function($node) {

      // Closure `$a= function() { ... };` vs. declaration `function a() { ... }`;
      // the latter explicitely becomes a statement by pushing a semicolon.
      if ('(' === $this->token->value) {
        $node->kind= 'closure';
        $signature= $this->signature();

        if ('use' === $this->token->value) {
          $this->token= $this->advance();
          $this->token= $this->advance();
          $use= [];
          while (')' !== $this->token->value) {
            if ('&' === $this->token->value) {
              $this->token= $this->advance();
              $use[]= '&$'.$this->token->value;
            } else {
              $use[]= '$'.$this->token->value;
            }
            $this->token= $this->advance();
            if (')' === $this->token->value) break;
            $this->token= $this->expect(',', 'use list');
          }
          $this->token= $this->expect(')');
        } else {
          $use= null;
        }

        $this->token= $this->expect('{');
        $statements= $this->statements();
        $this->token= $this->expect('}');

        $node->value= new ClosureExpression($signature, $use, $statements);
      } else {
        $node->kind= 'function';
        $name= $this->token->value;
        $this->token= $this->advance();
        $signature= $this->signature();

        if ('==>' === $this->token->value) {  // Compact syntax, terminated with ';'
          $n= new Node($this->token->symbol);
          $this->token= $this->advance();
          $n->value= $this->expressionWithThrows(0);
          $n->line= $this->token->line;
          $n->kind= 'return';
          $statements= [$n];
          $this->token= $this->next(';');
        } else {                              // Regular function
          $this->token= $this->expect('{');
          $statements= $this->statements();
          $this->token= $this->expect('}');
        }

        $this->queue= [$this->token];
        $this->token= new Node(self::symbol(';'));
        $node->value= new FunctionDeclaration($name, $signature, $statements);
      }

      return $node;
    });

    $this->prefix('static', function($node) {
      if ('variable' === $this->token->kind) {
        $node->kind= 'static';
        $node->value= [];
        while (';' !== $this->token->value) {
          $variable= $this->token->value;
          $this->token= $this->advance();

          if ('=' === $this->token->value) {
            $this->token= $this->advance();
            $initial= $this->expression(0);
          } else {
            $initial= null;
          }

          $node->value[$variable]= $initial;
          if (',' === $this->token->value) {
            $this->token= $this->advance();
          }
        }
      }
      return $node;
    });

    $this->prefix('goto', function($node) {
      $node->kind= 'goto';
      $node->value= $this->token->value;
      $this->token= $this->advance();
      return $node;
    });

    $this->prefix('(name)', function($node) {
      if (':' === $this->token->value) {
        $node->kind= 'label';
        $this->token= new Node(self::symbol(';'));
      }
      return $node;
    });

    $this->stmt('<?', function($node) {
      $node->kind= 'start';
      $node->value= $this->token->value;

      $this->token= $this->advance();
      return $node;
    });

    $this->stmt('{', function($node) {
      $node->kind= 'block';
      $node->value= $this->statements();
      $this->token= $this->advance();
      return $node;
    });

    $this->prefix('echo', function($node) {
      $node->kind= 'echo';
      $node->value= $this->arguments(';');
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
      if ('function' === $this->token->value) {
        $node->kind= 'importfunction';
        $this->token= $this->advance();
      } else if ('const' === $this->token->value) {
        $node->kind= 'importconst';
        $this->token= $this->advance();
      } else {
        $node->kind= 'import';
      }

      $import= $this->token->value;
      $this->token= $this->advance();

      if ('{' === $this->token->value) {
        $types= [];
        $this->token= $this->advance();
        while ('}' !== $this->token->value) {
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

          if (',' === $this->token->value) {
            $this->token= $this->advance();
          } else if ('}' === $this->token->value) {
            break;
          } else {
            $this->expect(', or }');
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

      $this->token= $this->next(';');
      $node->value= $types;
      return $node;
    });

    $this->stmt('if', function($node) {
      $this->token= $this->expect('(');
      $condition= $this->expression(0);
      $this->token= $this->expect(')');

      $when= $this->block();

      if ('else' === $this->token->value) {
        $this->token= $this->advance();
        $otherwise= $this->block();
      } else {
        $otherwise= null;
      }

      $node->value= new IfStatement($condition, $when, $otherwise);
      $node->kind= 'if';
      return $node;
    });

    $this->stmt('switch', function($node) {
      $this->token= $this->expect('(');
      $condition= $this->expression(0);
      $this->token= $this->expect(')');

      $cases= [];
      $this->token= $this->expect('{');
      while ('}' !== $this->token->value) {
        if ('default' === $this->token->value) {
          $this->token= $this->advance();
          $this->token= $this->expect(':');
          $cases[]= new CaseLabel(null, []);
        } else if ('case' === $this->token->value) {
          $this->token= $this->advance();
          $expr= $this->expression(0);
          $this->token= $this->expect(':');
          $cases[]= new CaseLabel($expr, []);
        } else {
          $cases[sizeof($cases) - 1]->body[]= $this->statement();
        }
      }
      $this->token= $this->advance();

      $node->value= new SwitchStatement($condition, $cases);
      $node->kind= 'switch';
      return $node;
    });

    $this->stmt('break', function($node) {
      if (';' === $this->token->value) {
        $node->value= null;
        $this->token= $this->advance();
      } else {
        $node->value= $this->expression(0);
        $this->token= $this->next(';');
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
        $this->token= $this->next(';');
      }

      $node->kind= 'continue';
      return $node;
    });

    $this->stmt('do', function($node) {
      $block= $this->block();

      $this->token= $this->next('while');
      $this->token= $this->next('(');
      $expression= $this->expression(0);
      $this->token= $this->next(')');
      $this->token= $this->next(';');

      $node->value= new DoLoop($expression, $block);
      $node->kind= 'do';
      return $node;
    });

    $this->stmt('while', function($node) {
      $this->token= $this->next('(');
      $expression= $this->expression(0);
      $this->token= $this->next(')');
      $block= $this->block();

      $node->value= new WhileLoop($expression, $block);
      $node->kind= 'while';
      return $node;
    });

    $this->stmt('for', function($node) {
      $this->token= $this->next('(');
      $init= $this->arguments(';');
      $this->token= $this->next(';');
      $cond= $this->arguments(';');
      $this->token= $this->next(';');
      $loop= $this->arguments(')');
      $this->token= $this->next(')');

      $block= $this->block();

      $node->value= new ForLoop($init, $cond, $loop, $block);
      $node->kind= 'for';
      return $node;
    });

    $this->stmt('foreach', function($node) {
      $this->token= $this->next('(');
      $expression= $this->expression(0);

      $this->token= $this->advance('as');
      $expr= $this->expression(0);

      if ('=>' === $this->token->value) {
        $this->token= $this->advance();
        $key= $expr;
        $value= $this->expression(0);
      } else {
        $key= null;
        $value= $expr;
      }

      $this->token= $this->next(')');

      $block= $this->block();
      $node->value= new ForeachLoop($expression, $key, $value, $block);
      $node->kind= 'foreach';
      return $node;
    });

    $this->stmt('throw', function($node) {
      $node->value= $this->expression(0);
      $node->kind= 'throw';
      $this->token= $this->next(';');
      return $node;      
    });

    $this->stmt('try', function($node) {
      $this->token= $this->next('{');
      $statements= $this->statements();
      $this->token= $this->next('}');

      $catches= [];
      while ('catch'  === $this->token->value) {
        $this->token= $this->advance();
        $this->token= $this->next('(');

        $types= [];
        while ('name' === $this->token->kind) {
          $types[]= $this->scope->resolve($this->token->value);
          $this->token= $this->advance();
          if ('|' !== $this->token->value) break;
          $this->token= $this->advance();
        }

        $variable= $this->token;
        $this->token= $this->advance();
        $this->token= $this->next(')');

        $this->token= $this->next('{');
        $catches[]= new CatchStatement($types, $variable->value, $this->statements());
        $this->token= $this->next('}');
      }

      if ('finally' === $this->token->value) {
        $this->token= $this->advance();
        $this->token= $this->next('{');
        $finally= $this->statements();
        $this->token= $this->next('}');
      } else {
        $finally= null;
      }

      $node->value= new TryStatement($statements, $catches, $finally);
      $node->kind= 'try';
      return $node;      
    });

    $this->stmt('return', function($node) {
      if (';' === $this->token->value) {
        $expr= null;
        $this->token= $this->advance();
      } else {
        $expr= $this->expression(0);
        $this->token= $this->next(';');
      }

      $node->value= $expr;
      $node->kind= 'return';
      return $node;
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
        $name= $this->token->value;
        $this->token= $this->advance();

        if ('(' === $this->token->value) {
          $this->token= $this->advance();
          $this->scope->annotations[$name]= $this->expression(0);
          $this->token= $this->next(')');
        } else {
          $this->scope->annotations[$name]= null;
        }

        if (',' === $this->token->value) {
          continue;
        } else if ('>>' === $this->token->value) {
          break;
        } else {
          $this->token= $this->next(', or >>', 'annotation');
        }
      } while (null !== $this->token->value);

      $this->token= $this->advance();
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
      $comment= $this->comment;
      $this->comment= null;

      $parents= [];
      if ('extends' === $this->token->value) {
        $this->token= $this->advance();
        do {
          $parents[]= $this->scope->resolve($this->token->value);
          $this->token= $this->advance();
          if (',' === $this->token->value) {
            $this->token= $this->advance();
          } else if ('{' === $this->token->value) {
            break;
          } else {
            $this->token= $this->next(', or {', 'interface parents');
          }
        } while (null !== $this->token->value);
      }

      $this->token= $this->expect('{');
      $body= $this->typeBody();
      $this->token= $this->expect('}');

      $node->value= new InterfaceDeclaration([], $type, $parents, $body, $this->scope->annotations, $comment);
      $node->kind= 'interface';
      $this->scope->annotations= [];
      return $node;
    });

    $this->stmt('trait', function($node) {
      $type= $this->scope->resolve($this->token->value);
      $this->token= $this->advance();
      $comment= $this->comment;
      $this->comment= null;

      $this->token= $this->expect('{');
      $body= $this->typeBody();
      $this->token= $this->expect('}');

      $node->value= new TraitDeclaration([], $type, $body, $this->scope->annotations, $comment);
      $node->kind= 'trait';
      $this->scope->annotations= [];
      return $node;
    });

    $this->stmt('using', function($node) {
      $this->token= $this->expect('(');
      $arguments= $this->arguments();
      $this->token= $this->expect(')');

      $this->token= $this->expect('{');
      $statements= $this->statements();
      $this->token= $this->expect('}');

      $node->value= new UsingStatement($arguments, $statements);
      $node->kind= 'using';
      return $node;
    });

    $this->body('use', function(&$body, $annotations, $modifiers) {
      $member= new Node($this->token->symbol);
      $member->kind= 'use';
      $member->line= $this->token->line;

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

      $member->value= new UseExpression($types, $aliases);
      $body[]= $member;
    });

    $this->body('const', function(&$body, $annotations, $modifiers) {
      $n= new Node($this->token->symbol);
      $n->kind= 'const';
      $this->token= $this->advance();

      $type= null;
      while (';' !== $this->token->value) {
        $member= clone $n;
        $member->line= $this->token->line;
        $first= $this->token;
        $this->token= $this->advance();

        // Untyped `const T = 5` vs. typed `const int T = 5`
        if ('=' === $this->token->value) {
          $name= $first->value;
        } else {
          $this->queue[]= $first;
          $this->queue[]= $this->token;
          $this->token= $first;

          $type= $this->type(false);
          $this->token= $this->advance();
          $name= $this->token->value;
          $this->token= $this->advance();
        }

        if (isset($body[$name])) {
          $this->raise('Cannot redeclare constant '.$name);
        }

        $this->token= $this->expect('=');
        $member->value= new Constant($modifiers, $name, $type, $this->expression(0));
        $body[$name]= $member;
        if (',' === $this->token->value) {
          $this->token= $this->expect(',');
        }
      }
      $this->token= $this->expect(';', 'constant declaration');
    });

    $this->body('@variable', function(&$body, $annotations, $modifiers) {
      $this->properties($body, $annotations, $modifiers, null);
    });

    $this->body('function', function(&$body, $annotations, $modifiers) {
      $member= new Node($this->token->symbol);
      $member->kind= 'method';
      $member->line= $this->token->line;
      $comment= $this->comment;
      $this->comment= null;

      $this->token= $this->advance();
      $name= $this->token->value;
      $lookup= $name.'()';
      if (isset($body[$lookup])) {
        $this->raise('Cannot redeclare method '.$lookup);
      }

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
        $this->warn('Hack language style compact functions are deprecated, please use `fn` syntax instead');

        $n= new Node($this->token->symbol);
        $n->line= $this->token->line;
        $this->token= $this->advance();
        $n->value= $this->expressionWithThrows(0);
        $n->kind= 'return';
        $statements= [$n];
        $this->token= $this->expect(';');
      } else {
        $this->token= $this->expect('{, ; or ==>', 'method declaration');
      }

      $member->value= new Method($modifiers, $name, $signature, $statements, $annotations, $comment);
      $body[$lookup]= $member;
    });
  }

  private function type($optional= true) {
    $t= [];
    do {
      $t[]= $this->type0($optional);
      if ('|' === $this->token->value) {
        $this->token= $this->advance();
        continue;
      }
      return 1 === sizeof($t) ? $t[0] : new UnionType($t);
    } while (true);
  }

  private function type0($optional) {
    if ('?' === $this->token->value) {
      $this->token= $this->advance();
      $type= '?'.$this->scope->resolve($this->token->value);
      $this->token= $this->advance();
    } else if ('(' === $this->token->value) {
      $this->token= $this->advance();
      $type= $this->type(false);
      $this->token= $this->advance();
      return $type;
    } else if ('name' === $this->token->kind && 'function' === $this->token->value) {
      $this->token= $this->advance();
      $this->token= $this->expect('(');
      $signature= [];
      if (')' !== $this->token->value) do {
        $signature[]= $this->type(false);
        if (',' === $this->token->value) {
          $this->token= $this->advance();
        } else if (')' === $this->token->value) {
          break;
        } else {
          $this->token= $this->next(', or )', 'function type');
        }
      } while (null !== $this->token->value);
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

    if ('<' === $this->token->value) {
      $this->token= $this->advance();
      $components= [];
      do {
        $components[]= $this->type(false);
        if (',' === $this->token->value) {
          $this->token= $this->advance();
        } else if ('>' === $this->token->symbol->id) {
          break;
        } else if ('>>' === $this->token->value) {
          $this->queue[]= $this->token= new Node(self::symbol('>'));
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

  private function properties(&$body, $annotations, $modifiers, $type) {
    $n= new Node($this->token->symbol);
    $n->kind= 'property';
    $comment= $this->comment;
    $this->comment= null;

    while (';' !== $this->token->value) {
      $member= clone $n;
      $member->line= $this->token->line;

      // Untyped `$a` vs. typed `int $a`
      if ('variable' === $this->token->kind) {
        $name= $this->token->value;
      } else {
        $type= $this->type(false);
        $name= $this->token->value;
      }

      $lookup= '$'.$name;
      if (isset($body[$lookup])) {
        $this->raise('Cannot redeclare property '.$lookup);
      }

      $this->token= $this->advance();
      if ('=' === $this->token->value) {
        $this->token= $this->advance();
        $member->value= new Property($modifiers, $name, $type, $this->expression(0), $annotations, $comment);
      } else {
        $member->value= new Property($modifiers, $name, $type, null, $annotations, $comment);
      }

      $body[$lookup]= $member;
      if (',' === $this->token->value) {
        $this->token= $this->advance();
      }
    }
    $this->token= $this->next(';', 'field declaration');
  }

  private function parameters() {
    static $promotion= ['private' => true, 'protected' => true, 'public' => true];

    $parameters= [];
    $annotations= [];
    while (')' !== $this->token->value) {
      if ('<<' === $this->token->value) {
        do {
          $this->token= $this->advance();

          $name= $this->token->value;
          $this->token= $this->advance();

          if ('(' === $this->token->value) {
            $this->token= $this->expect('(');
            $annotations[$name]= $this->expression(0);
            $this->token= $this->expect(')');
          } else {
            $annotations[$name]= null;
          }

          if (',' === $this->token->value) {
            continue;
          } else if ('>>' === $this->token->value) {
            break;
          } else {
            $this->token= $this->next(', or >>', 'parameter annotation');
          }
        } while (null !== $this->token->value);
        $this->token= $this->expect('>>', 'parameter annotation');
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
      if ('=' === $this->token->value) {
        $this->token= $this->advance();
        $default= $this->expression(0);
      }
      $parameters[]= new Parameter($name, $type, $default, $byref, $variadic, $promote, $annotations);

      if (')' === $this->token->value) break;
      $this->token= $this->expect(',', 'parameter list');
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
    if ('{'  === $this->token->value) {
      $this->token= $this->advance();
      $block= $this->statements();
      $this->token= $this->next('}');
      return $block;
    } else {
      return [$this->statement()];
    }
  }

  private function expressionWithThrows($bp) {
    if ('throw' === $this->token->value) {
      $expr= new Node($this->token->symbol);
      $expr->kind= 'throwexpression';
      $this->token= $this->advance();
      $expr->value= $this->expression($bp);
      return $expr;
    } else {
      return $this->expression($bp);
    }
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
        if (',' === $this->token->value) {
          $this->token= $this->expect(',');
        } else if ('{' === $this->token->value) {
          break;
        } else {
          $this->token= $this->next(', or {', 'interfaces list');
        }
      } while (null !== $this->token->value);
    }

    $this->token= $this->expect('{');
    $body= $this->typeBody();
    $this->token= $this->expect('}');

    $return= new ClassDeclaration($modifiers, $name, $parent, $implements, $body, $this->scope->annotations, $comment);
    $this->scope->annotations= [];
    return $return;
  }

  private function arguments($end= ')') {
    $arguments= [];
    while ($end !== $this->token->value) {
      $arguments[]= $this->expression(0, false);    // Undefined arguments are OK
      if (',' === $this->token->value) {
        $this->token= $this->advance();
      } else if ($end === $this->token->value) {
        break;
      } else {
        $this->expect($end.' or ,', 'argument list');
      }
    }
    return $arguments;
  }

  /**
   * Type body
   *
   * - `use [traits]`
   * - `[modifiers] int $t = 5`
   * - `[modifiers] const int T = 5`
   * - `[modifiers] function t(): int { }`
   */
  private function typeBody() {
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
    $annotations= [];
    while ('}' !== $this->token->value) {
      if (isset($modifier[$this->token->value])) {
        $modifiers[]= $this->token->value;
        $this->token= $this->advance();
      } else if ($f= $this->body[$this->token->value] ?? $this->body['@'.$this->token->kind] ?? null) {
        $f($body, $annotations, $modifiers);
        $modifiers= [];
        $annotations= [];
      } else if ('<<' === $this->token->symbol->id) {
        do {
          $this->token= $this->advance();

          $name= $this->token->value;
          $this->token= $this->advance();

          if ('(' === $this->token->value) {
            $this->token= $this->advance();
            $annotations[$name]= $this->expression(0);
            $this->token= $this->next(')');
          } else {
            $annotations[$name]= null;
          }

          if (',' === $this->token->value) {
            continue;
          } else if ('>>' === $this->token->value) {
            break;
          } else {
            $this->token= $this->next(', or >>', 'annotations');
          }
        } while (null !== $this->token->value);
        $this->token= $this->advance();
      } else if ($type= $this->type()) {
        $this->properties($body, $annotations, $modifiers, $type);
      } else {
        $this->raise(sprintf(
          'Expected a type, modifier, property, annotation, method or "}", have "%s"',
          $this->token->symbol->id
        ));
        $this->token= $this->advance();
        if (null === $this->token->value) break;
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
    while (null !== $this->token->value) {
      if (null === ($statement= $this->statement())) break;
      yield $statement;
    }
  }

  private function statements() {
    $statements= [];
    while ('}' !== $this->token->value) {
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

    if (';' !== $this->token->symbol->id) {
      $this->raise('Missing semicolon after '.$expr->kind.' statement', null, $expr->line);
    } else {
      $this->token= $this->advance();
    }

    return $expr;
  }

  // {{ setup
  private static function symbol($id, $lbp= 0) {
    if (isset(self::$symbols[$id])) {
      $symbol= self::$symbols[$id];
      if ($lbp > $symbol->lbp) {
        $symbol->lbp= $lbp;
      }
    } else {
      $symbol= new Symbol();
      $symbol->id= $id;
      $symbol->lbp= $lbp;
      self::$symbols[$id]= $symbol;
    }
    return $symbol;
  }

  private static function constant($id, $value) {
    $const= self::symbol($id);
    $const->nud= function($node) use($value) {
      $node->kind= 'literal';
      $node->value= $value;
      return $node;
    };
    return $const;
  }

  private function stmt($id, $func) {
    $stmt= self::symbol($id);
    $stmt->std= $func;
    return $stmt;
  }

  private function assignment($id) {
    $infix= self::symbol($id, 10);
    $infix->led= function($node, $left) use($id) {
      $node->kind= 'assignment';
      $node->value= new Assignment($left, $id, $this->expression(9));
      return $node;
    };
    return $infix;
  }

  private function infix($id, $bp, $led= null) {
    $infix= self::symbol($id, $bp);
    $infix->led= $led ?: function($node, $left) use($id, $bp) {
      $node->value= new BinaryExpression($left, $id, $this->expression($bp));
      $node->kind= 'binary';
      return $node;
    };
    return $infix;
  }

  private function infixr($id, $bp, $led= null) {
    $infix= self::symbol($id, $bp);
    $infix->led= $led ?: function($node, $left) use($id, $bp) {
      $node->value= new BinaryExpression($left, $id, $this->expression($bp - 1));
      $node->kind= 'binary';
      return $node;
    };
    return $infix;
  }

  private function infixt($id, $bp) {
    $infix= self::symbol($id, $bp);
    $infix->led= function($node, $left) use($id, $bp) {
      $node->value= new BinaryExpression($left, $id, $this->expressionWithThrows($bp - 1));
      $node->kind= 'binary';
      return $node;
    };
    return $infix;
  }

  private function prefix($id, $nud= null) {
    $prefix= self::symbol($id);
    $prefix->nud= $nud ?: function($node) use($id) {
      $node->value= new UnaryExpression($this->expression(70), $id);
      $node->kind= 'unary';
      return $node;
    };
    return $prefix;
  }

  private function suffix($id, $bp, $led= null) {
    $suffix= self::symbol($id, $bp);
    $suffix->led= $led ?: function($node, $left) use($id) {
      $node->value= new UnaryExpression($left, $id);
      $node->kind= 'unary';
      return $node;
    };
    return $suffix;
  }

  public function body($id, $func) {
    $this->body[$id]= $func->bindTo($this, self::class);
  }
  // }}}

  /**
   * Raise an error
   *
   * @param  string $error
   * @param  string $context
   * @param  int $line
   * @return void
   */
  private function raise($message, $context= null, $line= null) {
    $context && $message.= ' in '.$context;
    $this->errors[]= new Error($message, $this->file, $line ?: $this->token->line);
  }

  /**
   * Expect a given token, raise an error if another is encountered
   *
   * @param  string $id
   * @param  string $context
   * @return var
   */
  private function next($id, $context= null) {
    if ($id === $this->token->symbol->id) return $this->advance();

    $message= sprintf(
      'Expected "%s", have "%s"%s',
      $id,
      $this->token->value ?: $this->token->symbol->id,
      $context ? ' in '.$context : ''
    );
    $this->errors[]= new Error($message, $this->file, $this->token->line);
    return $this->token;
  }

  /**
   * Emit a warning
   *
   * @param  string $error
   * @param  string $context
   * @return void
   */
  private function warn($message, $context= null) {
    $context && $message.= ' ('.$context.')';
    trigger_error($message.' in '.$this->file.' on line '.$this->token->line);
  }

  /**
   * Expect a given token, raise an error if another is encountered
   *
   * @param  string $id
   * @param  string $context
   * @return var
   */
  private function expect($id, $context= null) {
    if ($id !== $this->token->symbol->id) {
      $message= sprintf(
        'Expected "%s", have "%s"%s',
        $id,
        $this->token->value ?: $this->token->symbol->id,
        $context ? ' in '.$context : ''
      );
      throw new Error($message, $this->file, $this->token->line);
    }

    return $this->advance();
  }

  private function advance() {
    static $line= 1;

    if ($this->queue) return array_shift($this->queue);

    while ($this->tokens->valid()) {
      $type= $this->tokens->key();
      list($value, $line)= $this->tokens->current();
      $this->tokens->next();
      if ('name' === $type) {
        $node= new Node(isset(self::$symbols[$value]) ? self::$symbols[$value] : self::symbol('(name)'));
        $node->kind= $type;
      } else if ('operator' === $type) {
        $node= new Node(self::symbol($value));
        $node->kind= $type;
      } else if ('string' === $type || 'integer' === $type || 'decimal' === $type) {
        $node= new Node(self::symbol('(literal)'));
        $node->kind= 'literal';
      } else if ('variable' === $type) {
        $node= new Node(self::symbol('(variable)'));
        $node->kind= 'variable';
      } else if ('comment' === $type) {
        $this->comment= $value;
        continue;
      } else {
        throw new Error('Unexpected token '.$value, $this->file, $line);
      }

      $node->value= $value;
      $node->line= $line;
      return $node;
    }

    $node= new Node(self::symbol('(end)'));
    $node->line= $line;
    return $node;
  }

  /**
   * Parses given file, returning AST nodes.
   *
   * @return iterable
   * @throws lang.ast.Errors
   */
  public function execute() {
    $this->token= $this->advance();
    try {
      foreach ($this->top() as $node) {
        yield $node;
      }
    } catch (Error $e) {
      $this->errors[]= $e;
    }

    if ($this->errors) {
      throw new Errors($this->errors, $this->file);
    }
  }
}