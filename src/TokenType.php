<?php

declare(strict_types = 1);

namespace Graphpinator\Tokenizer;

enum TokenType : string
{
    case NEWLINE = 'newline';
    case COMMENT = '#';
    case COMMA = ',';
    // lexical
    case NAME = 'name';
    case VARIABLE = '$';
    case DIRECTIVE = '@';
    case INT = 'int literal';
    case FLOAT = 'float literal';
    case STRING = 'string literal';
    // keywords
    case NULL = 'null';
    case TRUE = 'true';
    case FALSE = 'false';
    case QUERY = 'query';
    case MUTATION = 'mutation';
    case SUBSCRIPTION = 'subscription';
    case FRAGMENT = 'fragment';
    case ON = 'on'; // type condition
    // type system keywords
    case SCHEMA = 'schema';
    case TYPE = 'type';
    case INTERFACE = 'interface';
    case UNION = 'union';
    case INPUT = 'input';
    case ENUM = 'enum';
    case SCALAR = 'scalar';
    case IMPLEMENTS = 'implements';
    case REPEATABLE = 'repeatable';
    // punctators
    case AMP = '&'; // implements
    case PIPE = '|'; // union
    case EXCL = '!'; // not null
    case PAR_O = '('; // argument, variable, directive
    case PAR_C = ')';
    case CUR_O = '{'; // selection set
    case CUR_C = '}';
    case SQU_O = '['; // list
    case SQU_C = ']';
    case ELLIP = '...'; // fragment spread
    case COLON = ':'; // argument, variable, directive, field alias
    case EQUAL = '='; // default value

    public function isIgnorable() : bool
    {
        return match ($this) {
            self::COMMA,
            self::COMMENT,
            self::NEWLINE => true,
            default => false,
        };
    }
}
