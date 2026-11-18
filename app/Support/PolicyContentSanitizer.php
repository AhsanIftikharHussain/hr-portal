<?php

namespace App\Support;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

class PolicyContentSanitizer
{
    private HtmlSanitizer $sanitizer;

    public function __construct()
    {
        $config = (new HtmlSanitizerConfig)
            ->allowElement('p')
            ->allowElement('h1')
            ->allowElement('h2')
            ->allowElement('h3')
            ->allowElement('strong')
            ->allowElement('em')
            ->allowElement('u')
            ->allowElement('ul')
            ->allowElement('ol')
            ->allowElement('li')
            ->allowElement('blockquote')
            ->allowElement('br')
            ->allowElement('hr')
            ->allowElement('a', ['href', 'target'])
            ->allowLinkSchemes(['http', 'https', 'mailto'])
            ->allowRelativeLinks()
            ->forceAttribute('a', 'rel', 'noopener noreferrer')
            ->forceAttribute('a', 'target', '_blank')
            ->withMaxInputLength(400_000);

        $this->sanitizer = new HtmlSanitizer($config);
    }

    public function sanitize(string $content): string
    {
        return $this->sanitizer->sanitize($content);
    }

    public function hasMeaningfulContent(string $content): bool
    {
        $sanitizedContent = $this->sanitize($content);
        $plainText = html_entity_decode(strip_tags($sanitizedContent), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $plainTextWithoutWhitespace = preg_replace('/[\s\x{00A0}\x{200B}\x{FEFF}]+/u', '', $plainText);

        return $plainTextWithoutWhitespace !== null && $plainTextWithoutWhitespace !== '';
    }
}
