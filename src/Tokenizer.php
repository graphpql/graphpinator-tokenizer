<?php

declare(strict_types = 1);

namespace Graphpinator\Tokenizer;

final class Tokenizer implements \Iterator
{
    use \Nette\SmartObject;

    private const ESCAPE_MAP = [
        '"' => '"',
        '\\' => '\\',
        '/' => '/',
        'b' => "\u{0008}",
        'f' => "\u{000C}",
        'n' => "\u{000A}",
        'r' => "\u{000D}",
        't' => "\u{0009}",
    ];

    private ?Token $token = null;
    private ?int $tokenStartIndex = null;

    public function __construct(private \Graphpinator\Source\Source $source, private bool $skipNotRelevant = true)
    {
    }

    public function current() : Token
    {
        return $this->token;
    }

    public function key() : int
    {
        return $this->tokenStartIndex;
    }

    public function next() : void
    {
        $this->loadToken();
    }

    public function valid() : bool
    {
        if (!$this->token instanceof Token || !\is_int($this->tokenStartIndex)) {
            return false;
        }

        if ($this->skipNotRelevant && \array_key_exists($this->token->getType(), TokenType::IGNORABLE)) {
            $this->loadToken();

            return $this->valid();
        }

        return true;
    }

    public function rewind() : void
    {
        $this->source->rewind();
        $this->loadToken();
    }

    private function loadToken() : void
    {
        $this->skipWhitespace();

        if (!$this->source->hasChar()) {
            $this->token = null;
            $this->tokenStartIndex = null;

            return;
        }

        $location = $this->source->getLocation();
        $this->tokenStartIndex = $this->source->key();

        if ($this->source->getChar() === '_' || \ctype_alpha($this->source->getChar())) {
            $this->createWordToken($location);

            return;
        }

        if ($this->source->getChar() === '-' || \ctype_digit($this->source->getChar())) {
            $this->createNumericToken($location);

            return;
        }

        switch ($this->source->getChar()) {
            case '"':
                $quotes = $this->eatChars(static function (string $char) : bool {
                    return $char === '"';
                }, 3);

                switch (\strlen($quotes)) {
                    case 1:
                        $this->token = new \Graphpinator\Tokenizer\Token(TokenType::STRING, $location, $this->eatString($location));

                        return;
                    case 2:
                        $this->token = new \Graphpinator\Tokenizer\Token(TokenType::STRING, $location, '');

                        return;
                    default:
                        $this->token = new \Graphpinator\Tokenizer\Token(TokenType::STRING, $location, $this->eatBlockString($location));

                        return;
                }

                // fallthrough
            case \PHP_EOL:
                $this->token = new \Graphpinator\Tokenizer\Token(TokenType::NEWLINE, $location);
                $this->source->next();

                return;
            case TokenType::VARIABLE:
                $this->source->next();

                if (\ctype_alpha($this->source->getChar())) {
                    $this->token = new \Graphpinator\Tokenizer\Token(TokenType::VARIABLE, $location, $this->eatName());

                    return;
                }

                throw new \Graphpinator\Tokenizer\Exception\MissingVariableName($location);
            case TokenType::DIRECTIVE:
                $this->source->next();

                if (\ctype_alpha($this->source->getChar())) {
                    $this->token = new \Graphpinator\Tokenizer\Token(TokenType::DIRECTIVE, $location, $this->eatName());

                    return;
                }

                throw new \Graphpinator\Tokenizer\Exception\MissingDirectiveName($location);
            case TokenType::COMMENT:
                $this->source->next();
                $this->token = new \Graphpinator\Tokenizer\Token(TokenType::COMMENT, $location, $this->eatComment());

                return;
            case TokenType::COMMA:
            case TokenType::AMP:
            case TokenType::PIPE:
            case TokenType::EXCL:
            case TokenType::PAR_O:
            case TokenType::PAR_C:
            case TokenType::CUR_O:
            case TokenType::CUR_C:
            case TokenType::SQU_O:
            case TokenType::SQU_C:
            case TokenType::COLON:
            case TokenType::EQUAL:
                $this->token = new \Graphpinator\Tokenizer\Token($this->source->getChar(), $location);
                $this->source->next();

                return;
            case '.':
                $dots = $this->eatChars(static function (string $char) : bool {
                    return $char === '.';
                });

                if (\strlen($dots) !== 3) {
                    throw new \Graphpinator\Tokenizer\Exception\InvalidEllipsis($location);
                }

                $this->token = new \Graphpinator\Tokenizer\Token(TokenType::ELLIP, $location);

                return;
        }

        throw new \Graphpinator\Tokenizer\Exception\UnknownSymbol($location);
    }

    private function createWordToken(\Graphpinator\Common\Location $location) : void
    {
        $value = $this->eatName();

        switch ($value) {
            case OperationType::QUERY:
                $this->token = new \Graphpinator\Tokenizer\Token(TokenType::QUERY, $location);

                return;
            case OperationType::MUTATION:
                $this->token = new \Graphpinator\Tokenizer\Token(TokenType::MUTATION, $location);

                return;
            case OperationType::SUBSCRIPTION:
                $this->token = new \Graphpinator\Tokenizer\Token(TokenType::SUBSCRIPTION, $location);

                return;
        }

        switch (\strtolower($value)) {
            case TokenType::NULL:
                $this->token = new \Graphpinator\Tokenizer\Token(TokenType::NULL, $location);

                return;
            case TokenType::TRUE:
                $this->token = new \Graphpinator\Tokenizer\Token(TokenType::TRUE, $location);

                return;
            case TokenType::FALSE:
                $this->token = new \Graphpinator\Tokenizer\Token(TokenType::FALSE, $location);

                return;
            case TokenType::FRAGMENT:
                $this->token = new \Graphpinator\Tokenizer\Token(TokenType::FRAGMENT, $location);

                return;
            case TokenType::ON:
                $this->token = new \Graphpinator\Tokenizer\Token(TokenType::ON, $location);

                return;
            default:
                $this->token = new \Graphpinator\Tokenizer\Token(TokenType::NAME, $location, $value);
        }
    }

    private function createNumericToken(\Graphpinator\Common\Location $location) : void
    {
        $numberVal = $this->eatInt(true, false);

        if ($this->source->hasChar() && \in_array($this->source->getChar(), ['.', 'e', 'E'], true)) {
            if ($this->source->getChar() === '.') {
                $this->source->next();
                $numberVal .= '.' . $this->eatInt(false, true);
            }

            if ($this->source->hasChar() && \in_array($this->source->getChar(), ['e', 'E'], true)) {
                $this->source->next();

                if ($this->source->getChar() === '+') {
                    $this->source->next();
                }

                $numberVal .= 'e' . $this->eatInt(true, true);
            }

            $this->token = new \Graphpinator\Tokenizer\Token(TokenType::FLOAT, $location, $numberVal);
        } else {
            $this->token = new \Graphpinator\Tokenizer\Token(TokenType::INT, $location, $numberVal);
        }

        if ($this->source->hasChar() && \ctype_alpha($this->source->getChar())) {
            throw new \Graphpinator\Tokenizer\Exception\NumericLiteralFollowedByName($this->source->getLocation());
        }
    }

    private function skipWhiteSpace() : void
    {
        $this->eatChars(static function (string $char) : bool {
            return $char !== \PHP_EOL && \ctype_space($char);
        });
    }

    private function eatComment() : string
    {
        return $this->eatChars(static function (string $char) : bool {
            return $char !== \PHP_EOL;
        });
    }

    private function eatString(\Graphpinator\Common\Location $location) : string
    {
        $value = '';

        while ($this->source->hasChar()) {
            $char = $this->source->getChar();
            $this->source->next();

            switch ($char) {
                case \PHP_EOL:
                    throw new \Graphpinator\Tokenizer\Exception\StringLiteralNewLine($location);
                case '"':
                    return $value;
                case '\\':
                    $value .= $this->eatEscapeChar();

                    continue 2;
                default:
                    $value .= $char;
            }
        }

        throw new \Graphpinator\Tokenizer\Exception\StringLiteralWithoutEnd($location);
    }

    private function eatBlockString(\Graphpinator\Common\Location $location) : string
    {
        $value = '';

        while ($this->source->hasChar()) {
            switch ($this->source->getChar()) {
                case '"':
                    $quotes = $this->eatChars(static function (string $char) : bool {
                        return $char === '"';
                    }, 3);

                    if (\strlen($quotes) === 3) {
                        return $this->formatBlockString($value);
                    }

                    $value .= $quotes;

                    continue 2;
                case '\\':
                    $this->source->next();
                    $quotes = $this->eatChars(static function (string $char) : bool {
                        return $char === '"';
                    }, 3);

                    if (\strlen($quotes) === 3) {
                        $value .= '"""';
                    } else {
                        $value .= '\\' . $quotes;
                    }

                    continue 2;
                default:
                    $value .= $this->source->getChar();
                    $this->source->next();
            }
        }

        throw new \Graphpinator\Tokenizer\Exception\StringLiteralWithoutEnd($location);
    }

    private function formatBlockString(string $value) : string
    {
        $lines = \explode(\PHP_EOL, $value);

        while (\count($lines) > 0) {
            $first = \array_key_first($lines);

            if ($lines[$first] === '' || \ctype_space($lines[$first])) {
                unset($lines[$first]);

                continue;
            }

            $last = \array_key_last($lines);

            if ($lines[$last] === '' || \ctype_space($lines[$last])) {
                unset($lines[$last]);

                continue;
            }

            break;
        }

        $commonWhitespace = null;

        foreach ($lines as $line) {
            $trim = \ltrim($line);

            if ($trim === '') {
                continue;
            }

            $whitespaceCount = \strlen($line) - \strlen($trim);

            if ($commonWhitespace === null || $commonWhitespace > $whitespaceCount) {
                $commonWhitespace = $whitespaceCount;
            }
        }

        if (\in_array($commonWhitespace, [0, null], true)) {
            return \implode(\PHP_EOL, $lines);
        }

        $formattedLines = [];

        foreach ($lines as $line) {
            $formattedLines[] = \substr($line, $commonWhitespace);
        }

        return \implode(\PHP_EOL, $formattedLines);
    }

    private function eatEscapeChar() : string
    {
        $escapedChar = $this->source->getChar();

        if ($escapedChar === 'u') {
            $this->source->next();
            $hexDec = $this->eatChars(static function (string $char) : bool {
                return \ctype_xdigit($char);
            }, 4);

            if (\strlen($hexDec) !== 4) {
                throw new \Graphpinator\Tokenizer\Exception\StringLiteralInvalidEscape($this->source->getLocation());
            }

            return \mb_chr(\hexdec($hexDec), 'utf8');
        }

        $this->source->next();

        if (!\array_key_exists($escapedChar, self::ESCAPE_MAP)) {
            throw new \Graphpinator\Tokenizer\Exception\StringLiteralInvalidEscape($this->source->getLocation());
        }

        return self::ESCAPE_MAP[$escapedChar];
    }

    private function eatInt(bool $negative, bool $leadingZeros) : string
    {
        $sign = '';

        if ($this->source->getChar() === '-') {
            if (!$negative) {
                throw new \Graphpinator\Tokenizer\Exception\NumericLiteralNegativeFraction($this->source->getLocation());
            }

            $sign = '-';
            $this->source->next();
        }

        $digits = $this->eatChars(static function (string $char) : bool {
            return \ctype_digit($char);
        });
        $digitCount = \strlen($digits);

        if ($digitCount === 0) {
            throw new \Graphpinator\Tokenizer\Exception\NumericLiteralMalformed($this->source->getLocation());
        }

        if (!$leadingZeros && $digitCount > 1 && \strpos($digits, '0') === 0) {
            throw new \Graphpinator\Tokenizer\Exception\NumericLiteralLeadingZero($this->source->getLocation());
        }

        return $sign . $digits;
    }

    private function eatName() : string
    {
        return $this->eatChars(static function (string $char) : bool {
            return $char === '_' || \ctype_alnum($char);
        });
    }

    private function eatChars(callable $condition, int $limit = \PHP_INT_MAX) : string
    {
        $value = '';
        $count = 0;

        for (; $this->source->hasChar() && $count < $limit; $this->source->next()) {
            if ($condition($this->source->getChar()) === true) {
                $value .= $this->source->getChar();
                ++$count;

                continue;
            }

            break;
        }

        return $value;
    }
}
