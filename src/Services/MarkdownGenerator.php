<?php

declare(strict_types=1);

namespace Fanmade\AdrManager\Services;

use Fanmade\AdrManager\Data\AdrDto;
use Symfony\Component\Yaml\Yaml;

/**
 * Renders an {@see AdrDto} into a canonical ADR Markdown document: a YAML
 * front-matter block followed by the standardized Nygard headings. The output
 * is stable — re-rendering a parsed document produces byte-identical results.
 */
final class MarkdownGenerator
{
    public function render(AdrDto $adr): string
    {
        return $this->frontMatter($adr).$this->body($adr);
    }

    private function frontMatter(AdrDto $adr): string
    {
        $data = [
            'id' => $adr->id,
            'title' => $adr->title,
            'status' => $adr->status,
            'date' => $adr->date->toDateString(),
        ];

        if ($adr->author !== null) {
            $data['author'] = $adr->author;
        }

        if ($adr->supersedes !== []) {
            $data['supersedes'] = $adr->supersedes;
        }

        if ($adr->backlinks !== []) {
            $data['backlinks'] = $adr->backlinks;
        }

        $yaml = rtrim(Yaml::dump($data, inline: 4, indent: 2));

        return "---\n{$yaml}\n---\n\n";
    }

    private function body(AdrDto $adr): string
    {
        $sections = [
            'Context' => $adr->context,
            'Decision' => $adr->decision,
            'Consequences' => $adr->consequences,
        ];

        $body = "# {$adr->id}. {$adr->title}\n";

        foreach ($sections as $heading => $content) {
            $body .= "\n## {$heading}\n";
            $body .= $content === '' ? '' : "\n{$content}\n";
        }

        return rtrim($body, "\n")."\n";
    }
}
