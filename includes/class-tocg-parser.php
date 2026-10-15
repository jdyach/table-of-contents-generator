<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Parses headings out of HTML content using DOMDocument.
 *
 * Content is always parsed inside a wrapper element with a known id. Without
 * it, LIBXML_HTML_NOIMPLIED leaves no <body> to read the result back from,
 * and multiple top-level elements have no single parent.
 */
class TOCG_Parser {

    private const ROOT_ID = 'tocg-parse-root';

    /**
     * Extract headings from HTML content, in document order.
     *
     * @param string   $html Raw post HTML.
     * @param string[] $tags Heading tags to look for, e.g. ['h2', 'h3'].
     * @return array<int, array{tag: string, text: string, id: string}> Ordered list of headings.
     */
    public static function extract( string $html, array $tags ): array {
        if ( empty( $html ) || empty( $tags ) ) {
            return [];
        }

        [ , $nodes ] = self::load( $html, $tags );

        $headings = [];
        $seen_ids = [];

        foreach ( $nodes as $node ) {
            $text = trim( $node->textContent );
            if ( '' === $text ) {
                continue;
            }

            // Reuse an existing id attribute if present; otherwise generate a unique slug.
            $id = $node->getAttribute( 'id' );
            if ( '' === $id ) {
                $id = self::slug( $text, $seen_ids );
            }
            $seen_ids[] = $id;

            $headings[] = [
                'tag'  => strtolower( $node->nodeName ),
                'text' => $text,
                'id'   => $id,
            ];
        }

        return $headings;
    }

    /**
     * Add id attributes to headings in the HTML so anchor links work.
     *
     * Headings are matched to $headings by position (both lists come from the
     * same document-order query), so repeated heading text still gets the
     * correct, unique id.
     *
     * @param string                                                   $html
     * @param array<int, array{tag: string, text: string, id: string}> $headings
     * @return string Modified HTML.
     */
    public static function inject_ids( string $html, array $headings ): string {
        if ( empty( $headings ) ) {
            return $html;
        }

        $tags          = array_values( array_unique( array_column( $headings, 'tag' ) ) );
        [ $doc, $nodes ] = self::load( $html, $tags );

        $i = 0;
        foreach ( $nodes as $node ) {
            if ( '' === trim( $node->textContent ) ) {
                continue; // Skipped by extract() as well.
            }
            if ( ! isset( $headings[ $i ] ) ) {
                break;
            }
            if ( '' === $node->getAttribute( 'id' ) ) {
                $node->setAttribute( 'id', $headings[ $i ]['id'] );
            }
            $i++;
        }

        $root = $doc->getElementById( self::ROOT_ID );
        if ( ! $root ) {
            return $html;
        }

        $out = '';
        foreach ( $root->childNodes as $child ) {
            $out .= $doc->saveHTML( $child );
        }
        return $out;
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Parse HTML inside a wrapper and query the requested heading tags.
     *
     * XPath returns a union query in document order, so no manual sorting is
     * needed.
     *
     * @param string   $html HTML fragment.
     * @param string[] $tags Heading tags.
     * @return array{0: DOMDocument, 1: DOMNodeList}
     */
    private static function load( string $html, array $tags ): array {
        $doc = new DOMDocument();
        // Suppress errors from malformed HTML; the XML encoding hint keeps UTF-8 intact.
        libxml_use_internal_errors( true );
        $doc->loadHTML(
            '<?xml encoding="UTF-8"><div id="' . self::ROOT_ID . '">' . $html . '</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();

        $tags  = array_filter( $tags, fn( $tag ) => (bool) preg_match( '/^h[1-6]$/', $tag ) );
        $query = implode( ' | ', array_map( fn( $tag ) => '//' . $tag, $tags ) );

        $xpath = new DOMXPath( $doc );
        return [ $doc, $xpath->query( $query ) ];
    }

    /**
     * Convert heading text to a URL-friendly slug, avoiding duplicates.
     *
     * @param string[] $seen Ids already used on this page.
     */
    private static function slug( string $text, array $seen ): string {
        $base = sanitize_title( $text );
        $slug = $base;
        $i    = 1;
        while ( in_array( $slug, $seen, true ) ) {
            $slug = $base . '-' . $i;
            $i++;
        }
        return $slug;
    }
}
