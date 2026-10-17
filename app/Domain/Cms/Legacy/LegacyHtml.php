<?php

namespace App\Domain\Cms\Legacy;

use DOMDocument;
use DOMElement;
use DOMXPath;

/**
 * Turns legacy page HTML (inline styles, <input> "buttons", h1/h5, &nbsp; spacers) into clean markup
 * for a rich_text block. The CMS still runs HTMLPurifier on save; this only makes the input sensible.
 */
class LegacyHtml
{
    /** @return array{html: string, images: list<string>} images are the basenames referenced via <img> */
    public function clean(string $html, ?string $dropHeadingLike = null): array
    {
        $html = trim($html);

        if ($html === '') {
            return ['html' => '', 'images' => []];
        }

        $doc = new DOMDocument;
        libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="UTF-8"><div id="root">'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        $xp = new DOMXPath($doc);

        // <a><input type="button" value="About us"></a>  ->  <a>About us</a>
        foreach (iterator_to_array($xp->query('//input')) as $input) {
            /** @var DOMElement $input */
            $input->parentNode?->replaceChild($doc->createTextNode($input->getAttribute('value')), $input);
        }

        $images = [];
        foreach (iterator_to_array($xp->query('//img')) as $img) {
            /** @var DOMElement $img */
            $images[] = urldecode(basename(parse_url($img->getAttribute('src'), PHP_URL_PATH) ?: ''));
            $img->parentNode?->removeChild($img);
        }

        foreach (iterator_to_array($xp->query('//script|//style|//iframe|//object|//embed|//form')) as $node) {
            $node->parentNode?->removeChild($node);
        }

        foreach (['h1' => 'h2', 'h5' => 'h4', 'h6' => 'h4'] as $from => $to) {
            foreach (iterator_to_array($xp->query("//{$from}")) as $old) {
                $new = $doc->createElement($to);
                while ($old->firstChild) {
                    $new->appendChild($old->firstChild);
                }
                $old->parentNode?->replaceChild($new, $old);
            }
        }

        foreach (iterator_to_array($xp->query('//*[@style or @class or @id or @align or @width or @height]')) as $el) {
            if (! $el instanceof DOMElement) {
                continue;
            }
            foreach (['style', 'class', 'id', 'align', 'width', 'height'] as $attr) {
                $el->removeAttribute($attr);
            }
        }

        // Legacy pages repeat their own title as the first heading; the new page already shows it.
        if ($dropHeadingLike !== null) {
            $needle = mb_strtolower(trim(preg_replace('/[^\p{L}\p{N}]+/u', ' ', $dropHeadingLike)));
            foreach ($xp->query('//h2|//h3|//h4|//h5|//h6') as $heading) {
                $text = mb_strtolower(trim(preg_replace('/[^\p{L}\p{N}]+/u', ' ', $heading->textContent)));
                if ($text !== '' && ($text === $needle || str_contains($needle, $text) || str_contains($text, $needle))) {
                    $heading->parentNode?->removeChild($heading);
                }
                break; // only ever the first heading
            }
        }

        // <p><strong>The Start of the Tea Business</strong></p> is a subheading in the old editor's habits.
        foreach (iterator_to_array($xp->query('//p[count(*)=1 and strong]')) as $para) {
            $text = trim(str_replace("\xC2\xA0", ' ', $para->textContent));
            $strong = trim(str_replace("\xC2\xA0", ' ', $para->getElementsByTagName('strong')->item(0)->textContent ?? ''));
            if ($text !== '' && $text === $strong && mb_strlen($text) <= 90 && ! str_ends_with($text, '.')) {
                $h = $doc->createElement('h3', $text);
                $para->parentNode?->replaceChild($h, $para);
            }
        }

        // Spacer paragraphs and empty wrappers.
        foreach (iterator_to_array($xp->query('//p|//h2|//h3|//h4|//strong|//em|//span|//div[@id!="root"]')) as $el) {
            if ($el instanceof DOMElement && $this->isBlank($el)) {
                $el->parentNode?->removeChild($el);
            }
        }
        foreach (iterator_to_array($xp->query('//hr|//font|//center')) as $el) {
            if ($el->nodeName === 'hr') {
                $el->parentNode?->removeChild($el);
            } else {
                while ($el->firstChild) {
                    $el->parentNode?->insertBefore($el->firstChild, $el);
                }
                $el->parentNode?->removeChild($el);
            }
        }

        $root = $doc->getElementById('root') ?? $doc->documentElement;
        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= $doc->saveHTML($child);
        }

        return ['html' => trim(preg_replace("/\n{3,}/", "\n\n", $out)), 'images' => array_values(array_unique(array_filter($images)))];
    }

    private function isBlank(DOMElement $node): bool
    {
        if ($node->getElementsByTagName('img')->length > 0) {
            return false;
        }

        return trim(str_replace("\xC2\xA0", ' ', $node->textContent)) === '';
    }
}
