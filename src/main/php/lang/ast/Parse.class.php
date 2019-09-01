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
  private static $body= [];

  private $tokens, $file;
  private $errors= [];

  public $token, $scope;
  public $comment= null;
  public $queue= [];

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

    self::infixt('??', 30);
    self::infixt('?:', 30);
    self::infixr('&&', 30);
    self::infixr('||', 30);

    self::infixr('==', 40);
    self::infixr('===', 40);
    self::infixr('!=', 40);
    self::infixr('!==', 40);
    self::infixr('<', 40);
    self::infixr('<=', 40);
    self::infixr('>', 40);
    self::infixr('>=', 40);
    self::infixr('<=>', 40);

    self::infix('+', 50);
    self::infix('-', 50);
    self::infix('&', 50);
    self::infix('|', 50);
    self::infix('^', 50);
    self::suffix('++', 50);
    self::suffix('--', 50);

    self::infix('*', 60);
    self::infix('/', 60);
    self::infix('%', 60);
    self::infix('.', 60);
    self::infix('**', 60);

    self::infixr('<<', 70);
    self::infixr('>>', 70);

    self::infix('instanceof', 60, function($parse, $node, $left) {
      if ('name' === $parse->token->kind) {
        $node->value= new InstanceOfExpression($left, $parse->scope->resolve($parse->token->value));
        $parse->forward();
      } else {
        $node->value= new InstanceOfExpression($left, $parse->expression(0));
      }

      $node->kind= 'instanceof';
      return $node;
    });

    self::infix('->', 80, function($parse, $node, $left) {
      if ('{' === $parse->token->value) {
        $parse->forward();
        $expr= $parse->expression(0);
        $parse->expecting('}', 'dynamic member');
      } else {
        $expr= $parse->token;
        $parse->forward();
      }

      $node->value= new InstanceExpression($left, $expr);
      $node->kind= 'instance';
      return $node;
    });

    self::infix('::', 80, function($parse, $node, $left) {
      $node->value= new ScopeExpression($parse->scope->resolve($left->value), $parse->token);
      $node->kind= 'scope';
      $parse->forward();
      return $node;
    });

    self::infix('==>', 80, function($parse, $node, $left) {
      $parse->warn('Hack language style arrow functions are deprecated, please use `fn` syntax instead');

      $signature= new Signature([new Parameter($left->value, null)], null);
      if ('{' === $parse->token->value) {
        $parse->forward();
        $statements= $parse->statements();
        $parse->expecting('}', 'arrow function');
      } else {
        $statements= $parse->expressionWithThrows(0);
      }

      $node->value= new LambdaExpression($signature, $statements);
      $node->kind= 'lambda';
      return $node;
    });

    self::infix('(', 80, function($parse, $node, $left) {
      $arguments= $parse->expressions();
      $parse->expecting(')', 'invoke expression');
      $node->value= new InvokeExpression($left, $arguments);
      $node->kind= 'invoke';
      return $node;
    });

    self::infix('[', 80, function($parse, $node, $left) {
      if (']' === $parse->token->value) {
        $expr= null;
        $parse->forward();
      } else {
        $expr= $parse->expression(0);
        $parse->expecting(']', 'offset access');
      }

      $node->value= new OffsetExpression($left, $expr);
      $node->kind= 'offset';
      return $node;
    });

    self::infix('{', 80, function($parse, $node, $left) {
      $expr= $parse->expression(0);
      $parse->token= $parse->next('}');

      $node->value= new OffsetExpression($left, $expr);
      $node->kind= 'offset';
      return $node;
    });

    self::infix('?', 80, function($parse, $node, $left) {
      $when= $parse->expressionWithThrows(0);
      $parse->token= $parse->next(':');
      $else= $parse->expressionWithThrows(0);
      $node->value= new TernaryExpression($left, $when, $else);
      $node->kind= 'ternary';
      return $node;
    });

    self::prefix('@');
    self::prefix('&');
    self::prefix('~');
    self::prefix('!');
    self::prefix('+');
    self::prefix('-');
    self::prefix('++');
    self::prefix('--');
    self::prefix('clone');

    self::assignment('=');
    self::assignment('&=');
    self::assignment('|=');
    self::assignment('^=');
    self::assignment('+=');
    self::assignment('-=');
    self::assignment('*=');
    self::assignment('/=');
    self::assignment('.=');
    self::assignment('**=');
    self::assignment('>>=');
    self::assignment('<<=');
    self::assignment('??=');

    // This is ambiguous:
    //
    // - An arrow function `($a) ==> $a + 1`
    // - An expression surrounded by parentheses `($a ?? $b)->invoke()`;
    // - A cast `(int)$a` or `(int)($a / 2)`.
    //
    // Resolve by looking ahead after the closing ")"
    self::prefix('(', function($parse, $node) {
      static $types= [
        '<'        => true,
        '>'        => true,
        ','        => true,
        '?'        => true,
        ':'        => true
      ];

      $skipped= [$node, $parse->token];
      $cast= true;
      $level= 1;
      while ($level > 0 && null !== $parse->token->value) {
        if ('(' === $parse->token->value) {
          $level++;
        } else if (')' === $parse->token->value) {
          $level--;
        } else if ('name' !== $parse->token->kind && !isset($types[$parse->token->value])) {
          $cast= false;
        }
        $parse->forward();
        $skipped[]= $parse->token;
      }
      $parse->queue= array_merge($skipped, $parse->queue);

      if (':' === $parse->token->value || '==>' === $parse->token->value) {
        $parse->warn('Hack language style arrow functions are deprecated, please use `fn` syntax instead');

        $node->kind= 'lambda';
        $parse->forward();
        $signature= $parse->signature();
        $parse->forward();

        if ('{' === $parse->token->value) {
          $parse->forward();
          $statements= $parse->statements();
          $parse->expecting('}', 'arrow function');
        } else {
          $statements= $parse->expressionWithThrows(0);
        }

        $node->value= new LambdaExpression($signature, $statements);
      } else if ($cast && ('operator' !== $parse->token->kind || '(' === $parse->token->value || '[' === $parse->token->value)) {
        $node->kind= 'cast';

        $parse->forward();
        $parse->expecting('(', 'cast');
        $type= $parse->type0($parse, false);
        $parse->expecting(')', 'cast');
        $node->value= new CastExpression($type, $parse->expression(0));
      } else {
        $node->kind= 'braced';

        $parse->forward();
        $parse->expecting('(', 'braced');
        $node->value= $parse->expression(0);
        $parse->expecting(')', 'braced');
      }
      return $node;
    });

    self::prefix('[', function($parse, $node) {
      $values= [];
      while (']' !== $parse->token->value) {
        $expr= $parse->expression(0);

        if ('=>' === $parse->token->value) {
          $parse->forward();
          $values[]= [$expr, $parse->expression(0)];
        } else {
          $values[]= [null, $expr];
        }

        if (']' === $parse->token->value) {
          break;
        } else {
          $parse->expecting(',', 'array literal');
        }
      }

      $parse->expecting(']', 'array literal');
      $node->kind= 'array';
      $node->value= $values;
      return $node;
    });

    self::prefix('new', function($parse, $node) {
      $type= $parse->token;
      $parse->forward();

      $parse->expecting('(', 'new arguments');
      $arguments= $parse->expressions();
      $parse->expecting(')', 'new arguments');

      if ('variable' === $type->kind) {
        $node->value= new NewExpression('$'.$type->value, $arguments);
        $node->kind= 'new';
      } else if ('class' === $type->value) {
        $node->value= new NewClassExpression($parse->clazz(null), $arguments);
        $node->kind= 'newclass';
      } else {
        $node->value= new NewExpression($parse->scope->resolve($type->value), $arguments);
        $node->kind= 'new';
      }
      return $node;
    });

    self::prefix('yield', function($parse, $node) {
      if (';' === $parse->token->value) {
        $node->kind= 'yield';
        $node->value= new YieldExpression(null, null);
      } else if ('from' === $parse->token->value) {
        $parse->forward();
        $node->kind= 'from';
        $node->value= $parse->expression(0);
      } else {
        $node->kind= 'yield';
        $expr= $parse->expression(0);
        if ('=>' === $parse->token->value) {
          $parse->forward();
          $node->value= new YieldExpression($expr, $parse->expression(0));
        } else {
          $node->value= new YieldExpression(null, $expr);
        }
      }
      return $node;
    });

    self::prefix('...', function($parse, $node) {
      $node->kind= 'unpack';
      $node->value= $parse->expression(0);
      return $node;
    });

    self::prefix('fn', function($parse, $node) {
      $signature= $parse->signature();

      $parse->token= $parse->expect('=>');

      if ('{' === $parse->token->value) {
        $parse->token= $parse->expect('{');
        $statements= $parse->statements();
        $parse->token= $parse->expect('}');
      } else {
        $statements= $parse->expressionWithThrows(0);
      }

      $node->value= new LambdaExpression($signature, $statements);
      $node->kind= 'lambda';
      return $node;
    });


    self::prefix('function', function($parse, $node) {

      // Closure `$a= function() { ... };` vs. declaration `function a() { ... }`;
      // the latter explicitely becomes a statement by pushing a semicolon.
      if ('(' === $parse->token->value) {
        $node->kind= 'closure';
        $signature= $parse->signature();

        if ('use' === $parse->token->value) {
          $parse->forward();
          $parse->forward();
          $use= [];
          while (')' !== $parse->token->value) {
            if ('&' === $parse->token->value) {
              $parse->forward();
              $use[]= '&$'.$parse->token->value;
            } else {
              $use[]= '$'.$parse->token->value;
            }
            $parse->forward();
            if (')' === $parse->token->value) break;
            $parse->token= $parse->expect(',', 'use list');
          }
          $parse->token= $parse->expect(')');
        } else {
          $use= null;
        }

        $parse->token= $parse->expect('{');
        $statements= $parse->statements();
        $parse->token= $parse->expect('}');

        $node->value= new ClosureExpression($signature, $use, $statements);
      } else {
        $node->kind= 'function';
        $name= $parse->token->value;
        $parse->forward();
        $signature= $parse->signature();

        if ('==>' === $parse->token->value) {  // Compact syntax, terminated with ';'
          $n= new Node($parse->token->symbol);
          $parse->forward();
          $n->value= $parse->expressionWithThrows(0);
          $n->line= $parse->token->line;
          $n->kind= 'return';
          $statements= [$n];
          $parse->token= $parse->next(';');
        } else {                              // Regular function
          $parse->token= $parse->expect('{');
          $statements= $parse->statements();
          $parse->token= $parse->expect('}');
        }

        $parse->queue= [$parse->token];
        $parse->token= new Node(self::symbol(';'));
        $node->value= new FunctionDeclaration($name, $signature, $statements);
      }

      return $node;
    });

    self::prefix('static', function($parse, $node) {
      if ('variable' === $parse->token->kind) {
        $node->kind= 'static';
        $node->value= [];
        while (';' !== $parse->token->value) {
          $variable= $parse->token->value;
          $parse->forward();

          if ('=' === $parse->token->value) {
            $parse->forward();
            $initial= $parse->expression(0);
          } else {
            $initial= null;
          }

          $node->value[$variable]= $initial;
          if (',' === $parse->token->value) {
            $parse->forward();
          }
        }
      }
      return $node;
    });

    self::prefix('goto', function($parse, $node) {
      $node->kind= 'goto';
      $node->value= $parse->token->value;
      $parse->forward();
      return $node;
    });

    self::prefix('(name)', function($parse, $node) {
      if (':' === $parse->token->value) {
        $node->kind= 'label';
        $parse->token= new Node(self::symbol(';'));
      }
      return $node;
    });

    self::stmt('<?', function($parse, $node) {
      $node->kind= 'start';
      $node->value= $parse->token->value;

      $parse->forward();
      return $node;
    });

    self::stmt('{', function($parse, $node) {
      $node->kind= 'block';
      $node->value= $parse->statements();
      $parse->forward();
      return $node;
    });

    self::prefix('echo', function($parse, $node) {
      $node->kind= 'echo';
      $node->value= $parse->expressions(';');
      return $node;
    });

    self::stmt('namespace', function($parse, $node) {
      $node->kind= 'package';
      $node->value= $parse->token->value;

      $parse->forward();
      $parse->expecting(';', 'namespace');

      $parse->scope->package($node->value);
      return $node;
    });

    self::stmt('use', function($parse, $node) {
      if ('function' === $parse->token->value) {
        $node->kind= 'importfunction';
        $parse->forward();
      } else if ('const' === $parse->token->value) {
        $node->kind= 'importconst';
        $parse->forward();
      } else {
        $node->kind= 'import';
      }

      $import= $parse->token->value;
      $parse->forward();

      if ('{' === $parse->token->value) {
        $types= [];
        $parse->forward();
        while ('}' !== $parse->token->value) {
          $class= $import.$parse->token->value;

          $parse->forward();
          if ('as' === $parse->token->value) {
            $parse->forward();
            $types[$class]= $parse->token->value;
            $parse->scope->import($parse->token->value);
            $parse->forward();
          } else {
            $types[$class]= null;
            $parse->scope->import($class);
          }

          if (',' === $parse->token->value) {
            $parse->forward();
          } else if ('}' === $parse->token->value) {
            break;
          } else {
            $this->expect(', or }');
          }
        }
        $parse->forward();
      } else if ('as' === $parse->token->value) {
        $parse->forward();
        $types= [$import => $parse->token->value];
        $parse->scope->import($import, $parse->token->value);
        $parse->forward();
      } else {
        $types= [$import => null];
        $parse->scope->import($import);
      }

      $parse->expecting(';', 'use');
      $node->value= $types;
      return $node;
    });

    self::stmt('if', function($parse, $node) {
      $parse->expecting('(', 'if');
      $condition= $parse->expression(0);
      $parse->expecting(')', 'if');

      $when= $parse->block();

      if ('else' === $parse->token->value) {
        $parse->forward();
        $otherwise= $parse->block();
      } else {
        $otherwise= null;
      }

      $node->value= new IfStatement($condition, $when, $otherwise);
      $node->kind= 'if';
      return $node;
    });

    self::stmt('switch', function($parse, $node) {
      $parse->expecting('(', 'switch');
      $condition= $parse->expression(0);
      $parse->expecting(')', 'switch');

      $cases= [];
      $parse->expecting('{', 'switch');
      while ('}' !== $parse->token->value) {
        if ('default' === $parse->token->value) {
          $parse->forward();
          $parse->expecting(':', 'switch');
          $cases[]= new CaseLabel(null, []);
        } else if ('case' === $parse->token->value) {
          $parse->forward();
          $expr= $parse->expression(0);
          $parse->expecting(':', 'switch');
          $cases[]= new CaseLabel($expr, []);
        } else {
          $cases[sizeof($cases) - 1]->body[]= $parse->statement();
        }
      }
      $parse->forward();

      $node->value= new SwitchStatement($condition, $cases);
      $node->kind= 'switch';
      return $node;
    });

    self::stmt('break', function($parse, $node) {
      if (';' === $parse->token->value) {
        $node->value= null;
        $parse->forward();
      } else {
        $node->value= $parse->expression(0);
        $parse->expecting(';', 'break');
      }

      $node->kind= 'break';
      return $node;
    });

    self::stmt('continue', function($parse, $node) {
      if (';' === $parse->token->value) {
        $node->value= null;
        $parse->forward();
      } else {
        $node->value= $parse->expression(0);
        $parse->expecting(';', 'continue');
      }

      $node->kind= 'continue';
      return $node;
    });

    self::stmt('do', function($parse, $node) {
      $block= $parse->block();

      $parse->expecting('while', 'while');
      $parse->expecting('(', 'while');
      $expression= $parse->expression(0);
      $parse->expecting(')', 'while');
      $parse->expecting(';', 'while');

      $node->value= new DoLoop($expression, $block);
      $node->kind= 'do';
      return $node;
    });

    self::stmt('while', function($parse, $node) {
      $parse->expecting('(', 'while');
      $expression= $parse->expression(0);
      $parse->expecting(')', 'while');
      $block= $parse->block();

      $node->value= new WhileLoop($expression, $block);
      $node->kind= 'while';
      return $node;
    });

    self::stmt('for', function($parse, $node) {
      $parse->expecting('(', 'for');
      $init= $parse->expressions(';');
      $parse->expecting(';', 'for');
      $cond= $parse->expressions(';');
      $parse->expecting(';', 'for');
      $loop= $parse->expressions(')');
      $parse->expecting(')', 'for');

      $block= $parse->block();

      $node->value= new ForLoop($init, $cond, $loop, $block);
      $node->kind= 'for';
      return $node;
    });

    self::stmt('foreach', function($parse, $node) {
      $parse->expecting('(', 'foreach');
      $expression= $parse->expression(0);

      $parse->expecting('as', 'foreach');
      $expr= $parse->expression(0);

      if ('=>' === $parse->token->value) {
        $parse->forward();
        $key= $expr;
        $value= $parse->expression(0);
      } else {
        $key= null;
        $value= $expr;
      }

      $parse->expecting(')', 'foreach');

      $block= $parse->block();
      $node->value= new ForeachLoop($expression, $key, $value, $block);
      $node->kind= 'foreach';
      return $node;
    });

    self::stmt('throw', function($parse, $node) {
      $node->value= $parse->expression(0);
      $node->kind= 'throw';
      $parse->expecting(';', 'throw');
      return $node;      
    });

    self::stmt('try', function($parse, $node) {
      $parse->expecting('{', 'try');
      $statements= $parse->statements();
      $parse->expecting('}', 'try');

      $catches= [];
      while ('catch'  === $parse->token->value) {
        $parse->forward();
        $parse->expecting('(', 'try');

        $types= [];
        while ('name' === $parse->token->kind) {
          $types[]= $parse->scope->resolve($parse->token->value);
          $parse->forward();
          if ('|' !== $parse->token->value) break;
          $parse->forward();
        }

        $variable= $parse->token;
        $parse->forward();
        $parse->expecting(')', 'catch');

        $parse->expecting('{', 'catch');
        $catches[]= new CatchStatement($types, $variable->value, $parse->statements());
        $parse->expecting('}', 'catch');
      }

      if ('finally' === $parse->token->value) {
        $parse->forward();
        $parse->expecting('{', 'finally');
        $finally= $parse->statements();
        $parse->expecting('}', 'finally');
      } else {
        $finally= null;
      }

      $node->value= new TryStatement($statements, $catches, $finally);
      $node->kind= 'try';
      return $node;      
    });

    self::stmt('return', function($parse, $node) {
      if (';' === $parse->token->value) {
        $expr= null;
        $parse->forward();
      } else {
        $expr= $parse->expression(0);
        $parse->expecting(';', 'return');
      }

      $node->value= $expr;
      $node->kind= 'return';
      return $node;
    });

    self::stmt('abstract', function($parse, $node) {
      $parse->forward();
      $type= $parse->scope->resolve($parse->token->value);
      $parse->forward();

      $node->value= $parse->clazz($type, ['abstract']);
      $node->kind= 'class';
      return $node;
    });

    self::stmt('final', function($parse, $node) {
      $parse->forward();
      $type= $parse->scope->resolve($parse->token->value);
      $parse->forward();

      $node->value= $parse->clazz($type, ['final']);
      $node->kind= 'class';
      return $node;
    });

    self::stmt('<<', function($parse, $node) {
      do {
        $name= $parse->token->value;
        $parse->forward();

        if ('(' === $parse->token->value) {
          $parse->forward();
          $parse->scope->annotations[$name]= $parse->expression(0);
          $parse->expecting(')', 'annotations');
        } else {
          $parse->scope->annotations[$name]= null;
        }

        if (',' === $parse->token->value) {
          continue;
        } else if ('>>' === $parse->token->value) {
          break;
        } else {
          $parse->expecting(', or >>', 'annotation');
        }
      } while (null !== $parse->token->value);

      $parse->forward();
      $node->kind= 'annotation';
      return $node;
    });

    self::stmt('class', function($parse, $node) {
      $type= $parse->scope->resolve($parse->token->value);
      $parse->forward();

      $node->value= $parse->clazz($type);
      $node->kind= 'class';
      return $node;
    });

    self::stmt('interface', function($parse, $node) {
      $type= $parse->scope->resolve($parse->token->value);
      $parse->forward();
      $comment= $parse->comment;
      $parse->comment= null;

      $parents= [];
      if ('extends' === $parse->token->value) {
        $parse->forward();
        do {
          $parents[]= $parse->scope->resolve($parse->token->value);
          $parse->forward();
          if (',' === $parse->token->value) {
            $parse->forward();
          } else if ('{' === $parse->token->value) {
            break;
          } else {
            $parse->expecting(', or {', 'interface parents');
          }
        } while (null !== $parse->token->value);
      }

      $parse->expecting('{', 'interface');
      $body= $parse->typeBody();
      $parse->expecting('}', 'interface');

      $node->value= new InterfaceDeclaration([], $type, $parents, $body, $parse->scope->annotations, $comment);
      $node->kind= 'interface';
      $parse->scope->annotations= [];
      return $node;
    });

    self::stmt('trait', function($parse, $node) {
      $type= $parse->scope->resolve($parse->token->value);
      $parse->forward();
      $comment= $parse->comment;
      $parse->comment= null;

      $parse->expecting('{', 'trait');
      $body= $parse->typeBody();
      $parse->expecting('}', 'trait');

      $node->value= new TraitDeclaration([], $type, $body, $parse->scope->annotations, $comment);
      $node->kind= 'trait';
      $parse->scope->annotations= [];
      return $node;
    });

    self::body('use', function($parse, &$body, $annotations, $modifiers) {
      $member= new Node($parse->token->symbol);
      $member->kind= 'use';
      $member->line= $parse->token->line;

      $parse->forward();
      $types= [];
      do {
        $types[]= $parse->scope->resolve($parse->token->value);
        $parse->forward();
        if (',' === $parse->token->value) {
          $parse->forward();
          continue;
        } else {
          break;
        }
      } while ($parse->token->value);

      $aliases= [];
      if ('{' === $parse->token->value) {
        $parse->forward();
        while ('}' !== $parse->token->value) {
          $method= $parse->token->value;
          $parse->forward();
          if ('::' === $parse->token->value) {
            $parse->forward();
            $method= $parse->scope->resolve($method).'::'.$parse->token->value;
            $parse->forward();
          }
          $parse->expecting('as', 'use');
          $alias= $parse->token->value;
          $parse->forward();
          $parse->expecting(';', 'use');
          $aliases[$method]= $alias;
        }
        $parse->expecting('}', 'use');
      } else {
        $parse->expecting(';', 'use');
      }

      $member->value= new UseExpression($types, $aliases);
      $body[]= $member;
    });

    self::body('const', function($parse, &$body, $annotations, $modifiers) {
      $n= new Node($parse->token->symbol);
      $n->kind= 'const';
      $parse->forward();

      $type= null;
      while (';' !== $parse->token->value) {
        $member= clone $n;
        $member->line= $parse->token->line;
        $first= $parse->token;
        $parse->forward();

        // Untyped `const T = 5` vs. typed `const int T = 5`
        if ('=' === $parse->token->value) {
          $name= $first->value;
        } else {
          $parse->queue[]= $first;
          $parse->queue[]= $parse->token;
          $parse->token= $first;

          $type= $parse->type($parse, false);
          $parse->forward();
          $name= $parse->token->value;
          $parse->forward();
        }

        if (isset($body[$name])) {
          $parse->raise('Cannot redeclare constant '.$name);
        }

        $parse->token= $parse->expect('=');
        $member->value= new Constant($modifiers, $name, $type, $parse->expression(0));
        $body[$name]= $member;
        if (',' === $parse->token->value) {
          $parse->forward();
        }
      }
      $parse->expecting(';', 'constant declaration');
    });

    self::body('@variable', function($parse, &$body, $annotations, $modifiers) {
      $parse->properties($parse, $body, $annotations, $modifiers, null);
    });

    self::body('function', function($parse, &$body, $annotations, $modifiers) {
      $member= new Node($parse->token->symbol);
      $member->kind= 'method';
      $member->line= $parse->token->line;
      $comment= $parse->comment;
      $parse->comment= null;

      $parse->forward();
      $name= $parse->token->value;
      $lookup= $name.'()';
      if (isset($body[$lookup])) {
        $parse->raise('Cannot redeclare method '.$lookup);
      }

      $parse->forward();
      $signature= $parse->signature();

      if ('{' === $parse->token->value) {          // Regular body
        $parse->forward();
        $statements= $parse->statements();
        $parse->expecting('}', 'method declaration');
      } else if (';' === $parse->token->value) {   // Abstract or interface method
        $statements= null;
        $parse->expecting(';', 'method declaration');
      } else if ('==>' === $parse->token->value) { // Compact syntax, terminated with ';'
        $parse->warn('Hack language style compact functions are deprecated, please use `fn` syntax instead');

        $n= new Node($parse->token->symbol);
        $n->line= $parse->token->line;
        $parse->forward();
        $n->value= $parse->expressionWithThrows(0);
        $n->kind= 'return';
        $statements= [$n];
        $parse->expecting(';', 'method declaration');
      } else {
        $parse->expecting('{, ; or ==>', 'method declaration');
      }

      $member->value= new Method($modifiers, $name, $signature, $statements, $annotations, $comment);
      $body[$lookup]= $member;
    });
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
  }

  private function type($parse, $optional= true) {
    $t= [];
    do {
      $t[]= $this->type0($parse, $optional);
      if ('|' === $this->token->value) {
        $parse->forward();
        continue;
      }
      return 1 === sizeof($t) ? $t[0] : new UnionType($t);
    } while (true);
  }

  private function type0($parse, $optional) {
    if ('?' === $this->token->value) {
      $this->token= $this->advance();
      $type= '?'.$parse->scope->resolve($this->token->value);
      $this->token= $this->advance();
    } else if ('(' === $this->token->value) {
      $this->token= $this->advance();
      $type= $this->type($parse, false);
      $this->token= $this->advance();
      return $type;
    } else if ('name' === $this->token->kind && 'function' === $this->token->value) {
      $this->token= $this->advance();
      $this->token= $this->expect('(');
      $signature= [];
      if (')' !== $this->token->value) do {
        $signature[]= $this->type($parse, false);
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
      return new FunctionType($signature, $this->type($parse, false));
    } else if ('name' === $this->token->kind) {
      $type= $parse->scope->resolve($this->token->value);
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
        $components[]= $this->type($parse, false);
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

  private function properties($parse, &$body, $annotations, $modifiers, $type) {
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
        $type= $this->type($parse, false);
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

  private function parameters($parse) {
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

      $type= $this->type($parse, );

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

  // {{ setup
  public static function symbol($id, $lbp= 0) {
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

  public static function constant($id, $value) {
    $const= self::symbol($id);
    $const->nud= function($parse, $node) use($value) {
      $node->kind= 'literal';
      $node->value= $value;
      return $node;
    };
    return $const;
  }

  public static function assignment($id) {
    $infix= self::symbol($id, 10);
    $infix->led= function($parse, $node, $left) use($id) {
      $node->kind= 'assignment';
      $node->value= new Assignment($left, $id, $parse->expression(9));
      return $node;
    };
    return $infix;
  }

  public static function infix($id, $bp, $led= null) {
    $infix= self::symbol($id, $bp);
    $infix->led= $led ?: function($parse, $node, $left) use($id, $bp) {
      $node->value= new BinaryExpression($left, $id, $parse->expression($bp));
      $node->kind= 'binary';
      return $node;
    };
    return $infix;
  }

  public static function infixr($id, $bp, $led= null) {
    $infix= self::symbol($id, $bp);
    $infix->led= $led ?: function($parse, $node, $left) use($id, $bp) {
      $node->value= new BinaryExpression($left, $id, $parse->expression($bp - 1));
      $node->kind= 'binary';
      return $node;
    };
    return $infix;
  }

  public static function infixt($id, $bp) {
    $infix= self::symbol($id, $bp);
    $infix->led= function($parse, $node, $left) use($id, $bp) {
      $node->value= new BinaryExpression($left, $id, $parse->expressionWithThrows($bp - 1));
      $node->kind= 'binary';
      return $node;
    };
    return $infix;
  }

  public static function prefix($id, $nud= null) {
    $prefix= self::symbol($id);
    $prefix->nud= $nud ?: function($parse, $node) use($id) {
      $node->value= new UnaryExpression($parse->expression(70), $id);
      $node->kind= 'unary';
      return $node;
    };
    return $prefix;
  }

  public static function suffix($id, $bp, $led= null) {
    $suffix= self::symbol($id, $bp);
    $suffix->led= $led ?: function($parse, $node, $left) use($id) {
      $node->value= new UnaryExpression($left, $id);
      $node->kind= 'unary';
      return $node;
    };
    return $suffix;
  }

  /**
   * Statement
   *
   * @param  string $id
   * @param  function(lang.ast.Node): lang.ast.Node
   */
  public static function stmt($id, $func) {
    $stmt= self::symbol($id);
    $stmt->std= $func;
  }

  /**
   * Type body parsing
   *
   * @param  string $id
   * @param  function([:string], [:string], string[]): void
   */
  public static function body($id, $func) {
    self::$body[$id]= $func;
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
   * Type body
   *
   * - `use [traits]`
   * - `[modifiers] int $t = 5`
   * - `[modifiers] const int T = 5`
   * - `[modifiers] function t(): int { }`
   */
  public function typeBody() {
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
      } else if (isset(self::$body[$k= $this->token->value])
        ? ($f= self::$body[$k])
        : (isset(self::$body[$k= '@'.$this->token->kind]) ? ($f= self::$body[$k]) : null)
      ) {
        $f($this, $body, $annotations, $modifiers);
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
      } else if ($type= $this->type($this)) {
        $this->properties($this, $body, $annotations, $modifiers, $type);
        $modifiers= [];
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

  public function signature() {
    $this->token= $this->expect('(');
    $parameters= $this->parameters($this);
    $this->token= $this->expect(')');

    if (':' === $this->token->value) {
      $this->token= $this->advance();
      $return= $this->type($this);
    } else {
      $return= null;
    }

    return new Signature($parameters, $return);
  }

   public function block() {
    if ('{'  === $this->token->value) {
      $this->token= $this->advance();
      $block= $this->statements();
      $this->token= $this->next('}');
      return $block;
    } else {
      return [$this->statement()];
    }
  }

  public function clazz($name, $modifiers= []) {
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

  public function expressionWithThrows($bp) {
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

  public function expression($rbp) {
    $t= $this->token;
    $this->token= $this->advance();
    $left= $t->symbol->nud ? $t->symbol->nud->__invoke($this, $t) : $t;

    while ($rbp < $this->token->symbol->lbp) {
      $t= $this->token;
      $this->token= $this->advance();
      $left= $t->symbol->led ? $t->symbol->led->__invoke($this, $t, $left) : $t;
    }

    return $left;
  }

  public function expressions($end= ')') {
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

  public function statements() {
    $statements= [];
    while ('}' !== $this->token->value) {
      if (null === ($statement= $this->statement())) break;
      $statements[]= $statement;
    }
    return $statements;
  }

  public function statement() {
    if ($this->token->symbol->std) {
      $t= $this->token;
      $this->forward();
      return $t->symbol->std->__invoke($this, $t);
    }

    $expr= $this->expression(0);

    if (';' !== $this->token->symbol->id) {
      $this->raise('Missing semicolon after '.$expr->kind.' statement', null, $expr->line);
    } else {
      $this->forward();
    }

    return $expr;
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
      $this->token= $node;
      return;
    }

    $node= new Node(self::symbol('(end)'));
    $node->line= $line;
    $this->token= $node;
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
    $this->errors[]= new Error($message, $this->file, $this->token->line);
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
        if (null === ($statement= $this->statement())) break;
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