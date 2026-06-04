<?php

namespace Hyvor\FilterQ;

use Hyvor\FilterQ\Exceptions\ParserException;

class Parser
{
    private string $input;

    /**
     * @var array<string, array<mixed>>
     */
    public array $output = [];

    private int $i = 0;

    private const ERROR_AND_OR_TOGETHER = 'AND and OR cannot be combined together. Use parentheses to separate them';
    private const ERROR_NO_CLOSING_PARENTHESIS = 'Closing ) not found';
    private const ERROR_NO_CLOSING_QUOTE = "Closing quote (') not found";

    public function __construct(string $input)
    {
        $this->input = trim($input);

        if (
            !preg_match('/\((?:[^)(]+|(?R))*+\)/', $this->input, $matches) ||
            strlen($matches[0]) !== strlen($input)
        ) {
            $this->input = "($this->input)";
        }

        $this->output = $this->parseParentheses();
    }

    /**
     * @return array<string, array<mixed>>
     * @throws ParserException
     */
    private function parseParentheses(): array
    {
        $this->skipWhitespaces();
        $this->i++;

        /** @var array<mixed> $output */
        $output = [];
        $currentLogic = null;

        while (
            $this->validateEndOfInput(self::ERROR_NO_CLOSING_PARENTHESIS) &&
            $this->input[$this->i] !== ')'
        ) {
            $this->skipWhitespaces();

            if ($this->input[$this->i] === '(') {
                $parsed = $this->parseParentheses();
                $output[] = $parsed;
                continue;
            }

            $matched = preg_match(
                '/^([a-zA-Z0-9_.]+)(?:\s+)?(=|!=|>=|<=|>|<|[!@#$%^&*~`?]{1,2})/',
                $this->getNextPart(),
                $keyMatches
            );

            if ($matched) {
                $fullMatch = $keyMatches[0];
                $key = $keyMatches[1];
                $operator = $keyMatches[2];

                $this->i += strlen($fullMatch);

                $value = $this->parseValue();

                $output[] = [$key, $operator, $value];

                continue;
            }

            if ($this->input[$this->i] === '&') {
                if ($currentLogic === 'or') {
                    throw new ParserException(self::ERROR_AND_OR_TOGETHER);
                }
                $currentLogic = 'and';
                $this->i++;
                continue;
            }

            if ($this->input[$this->i] === '|') {
                if ($currentLogic === 'and') {
                    throw new ParserException(self::ERROR_AND_OR_TOGETHER);
                }
                $currentLogic = 'or';
                $this->i++;
                continue;
            }

            if ($this->input[$this->i] !== ')') {
                $this->i++;
            }
        }

        $this->i++;

        $currentLogic ??= 'and';
        return [
            $currentLogic => $output
        ];
    }

    private function parseValue(): mixed
    {
        $this->skipWhitespaces();

        return $this->parseNumber() ??
            $this->parseString() ??
            $this->parseKeyword('true', true) ??
            $this->parseKeyword('false', false) ??
            $this->parseKeyword('null', null);
    }

    private function parseNumber(): int|float|null
    {
        if (
            preg_match(
                '/(^-?\d+(?:(\.)(\d+))?)(?:[^\d]|$)/',
                $this->getNextPart(),
                $numberMatches
            )
        ) {
            $number = $numberMatches[1];
            $isFloat = $numberMatches[2] ?? false;

            $this->i += strlen($number);

            if ($isFloat) {
                return (float) $number;
            } else {
                return (int) $number;
            }
        }

        return null;
    }

    private function parseString(): ?string
    {
        if ($this->input[$this->i] === "'") {
            $this->i++;

            $string = "";

            while (
                $this->validateEndOfInput(self::ERROR_NO_CLOSING_QUOTE) &&
                $this->input[$this->i] !== "'"
            ) {
                if ($this->input[$this->i] === "\\") {
                    $nextChar = $this->input[$this->i + 1];
                    if ($nextChar === "'" || $nextChar === '\\') {
                        $string .= $nextChar;
                        $this->i += 2;
                    }
                } else {
                    $string .= $this->input[$this->i];
                    $this->i++;
                }
            }

            $this->i++;

            return $string;
        } elseif (
            preg_match('/^[a-zA-Z_][a-zA-Z0-9_-]+/', $this->getNextPart(), $withoutQuotesMatches)
        ) {
            $string = $withoutQuotesMatches[0];

            if (in_array($string, ['true', 'false', 'null'], true)) {
                return null;
            }

            $this->i += strlen($string);

            return $string;
        }

        return null;
    }

    private function parseKeyword(string $string, ?bool $value): ?bool
    {
        if (substr($this->input, $this->i, strlen($string)) === $string) {
            $this->i += strlen($string);
            return $value;
        }

        return null;
    }

    private function getNextPart(): string
    {
        return substr($this->input, $this->i);
    }

    private function skipWhitespaces(): void
    {
        while (
            isset($this->input[$this->i]) && (
                $this->input[$this->i] === " " ||
                $this->input[$this->i] === "\n" ||
                $this->input[$this->i] === "\t" ||
                $this->input[$this->i] === "\r"
            )
        ) {
            $this->i++;
        }
    }

    /**
     * @throws ParserException
     */
    private function validateEndOfInput(string $error): bool
    {
        if (!isset($this->input[$this->i])) {
            throw new ParserException($error);
        }

        return true;
    }

    /**
     * @return array<string, array<mixed>>
     */
    public static function parse(string $input): array
    {
        return (new self($input))->output;
    }
}
