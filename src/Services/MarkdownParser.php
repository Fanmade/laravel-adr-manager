<?php

declare(strict_types=1);

namespace Fanmade\AdrManager\Services;

use Fanmade\AdrManager\Data\AdrDto;
use Symfony\Component\Yaml\Yaml;

/**
 * Parses an ADR Markdown document into an {@see AdrDto}, separating the YAML
 * front-matter (metadata) from the prose body sections (Context, Decision,
 * Consequences).
 */
final class MarkdownParser
{
    /**
     * Body headings recognised as record sections, keyed by their DTO field.
     *
     * @var list<string>
     */
    private const array SECTION_KEYS = ['context', 'decision', 'consequences'];

    public function parse(string $raw): AdrDto
    {
        $normalized = str_replace(["\r\n", "\r"], "\n", $raw);

        [$frontMatter, $body] = $this->split($normalized);

        return AdrDto::fromArray([...$frontMatter, ...$this->extractSections($body)]);
    }

    /**
     * @return array{0: array<string, mixed>, 1: string}
     */
    private function split(string $document): array
    {
        if (preg_match('/^---\n(.*?)\n---[ \t]*(?:\n(.*))?$/s', $document, $matches) === 1) {
            $parsed = Yaml::parse($matches[1], Yaml::PARSE_DATETIME);

            return [
                $this->stringKeyed(is_array($parsed) ? $parsed : []),
                $matches[2] ?? '',
            ];
        }

        return [[], $document];
    }

    /**
     * @param  array<array-key, mixed>  $data
     * @return array<string, mixed>
     */
    private function stringKeyed(array $data): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            $result[(string) $key] = $value;
        }

        return $result;
    }

    /**
     * @return array<string, string>
     */
    private function extractSections(string $body): array
    {
        $sections = [];
        $current = null;
        /** @var list<string> $buffer */
        $buffer = [];

        foreach (explode("\n", $body) as $line) {
            if (preg_match('/^##[ \t]+(.+?)[ \t]*$/', $line, $matches) === 1) {
                if ($current !== null) {
                    $sections[$current] = trim(implode("\n", $buffer));
                }

                $heading = strtolower(trim($matches[1]));
                $current = in_array($heading, self::SECTION_KEYS, true) ? $heading : null;
                $buffer = [];

                continue;
            }

            if ($current !== null) {
                $buffer[] = $line;
            }
        }

        if ($current !== null) {
            $sections[$current] = trim(implode("\n", $buffer));
        }

        return $sections;
    }
}
