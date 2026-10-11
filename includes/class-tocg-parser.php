<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Parses headings out of HTML content using DOMDocument.
 */
class TOCG_Parser {

    /**
     * Extract headings from HTML content.
     *
     * @param string   $html       Raw post HTML.
     * @param string[] $tags       Heading tags to look for, e.g. ['h2', 'h3'].
     * @return array<int, array{tag: string, text: string, id: string}> Ordered list of headings.
     */
    public static function extract( string $html, array $tags ): array {
        if ( empty( $html ) || empty( $tags ) ) {
            return [];
        }

        $doc = new DOMDocument();
        // Suppress errors from malformed HTML; UTF-8 wrapper prevents charset mangling.
        libxml_use_internal_errors( true );
        $doc->loadHTML( '<?xml encoding="UTF-8">' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
        libxml_clear_errors();

        $headings = [];
        $seen_ids = [];

        foreach ( $tags as $tag ) {
            $nodes = $doc->getElementsByTagName( $tag );
            // iterator_to_array so we don't mutate a live NodeList while reading.
            foreach ( iterator_to_array( $nodes ) as $node ) {
                $text = trim( $node->textContent );
                if ( empty( $text ) ) {
                    continue;
                }

                // Reuse existing id attribute if present; otherwise generate a slug.
                $id = $node->getAttribute( 'id' );
                if ( empty( $id ) ) {
                    $id = self::slug( $text, $seen_ids );
                }
                $seen_ids[] = $id;

                // Record DOM position so we can sort below.
                $headings[] = [
                    'tag'      => $tag,
                    'text'     => $text,
                    'id'       => $id,
                    '_pos'     => self::node_position( $node ),
                ];
            }
        }

        // Sort by document order.
        usort( $headings, fn( $a, $b ) => $a['_pos'] <=> $b['_pos'] );

        // Drop the internal sort key.
        return array_map( fn( $h ) => [
            'tag'  => $h['tag'],
            'text' => $h['text'],
            'id'   => $h['id'],
        ], $headings );
    }

    /**
     * Add id attributes to headings in the HTML so anchor links work.
     *
     * @param string                                                     $html
     * @param array<int, array{tag: string, text: string, id: string}>   $headings
     * @return string Modified HTML.
     */
    public static function inject_ids( string $html, array $headings ): string {
        // Build a lookup: text → id (first occurrence wins).
        $map = [];
        foreach ( $headings as $h ) {
            $key = $h['tag'] . '|' . $h['text'];
            if ( ! isset( $map[ $key ] ) ) {
                $map[ $key ] = $h['id'];
            }
        }

        // Use DOMDocument to set id attributes without regex on HTML.
        $doc = new DOMDocument();
        libxml_use_internal_errors( true );
        $doc->loadHTML( '<?xml encoding="UTF-8">' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
        libxml_clear_errors();

        foreach ( array_unique( array_column( $headings, 'tag' ) ) as $tag ) {
            foreach ( iterator_to_array( $doc->getElementsByTagName( $tag ) ) as $node ) {
                $text = trim( $node->textContent );
                $key  = $tag . '|' . $text;
                if ( isset( $map[ $key ] ) && ! $node->getAttribute( 'id' ) ) {
                    $node->setAttribute( 'id', $map[ $key ] );
                }
            }
        }

        // saveHTML on the full doc adds <html><body> wrappers; extract just the body children.
        $body = $doc->getElementsByTagName( 'body' )->item( 0 );
        if ( ! $body ) {
            return $html;
        }

        $out = '';
        foreach ( $body->childNodes as $child ) {
            $out .= $doc->saveHTML( $child );
        }
        return $out;
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Convert heading text to a URL-friendly slug, avoiding duplicates.
     *
     * @param string[] $seen
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

    /**
     * Return a comparable document-order integer for a DOM node.
     * We walk the DOM to get a reliable depth-first index.
     */
    private static function node_position( DOMNode $node ): int {
        static $cache = [];
        $id = spl_object_id( $node );
        if ( isset( $cache[ $id ] ) ) {
            return $cache[ $id ];
        }
        $pos  = 0;
        $iter = new RecursiveIteratorIterator(
            new RecursiveArrayIterator( [] ) // placeholder — we walk the owner document
        );
        // Simple approach: traverse from document root, count nodes until we find ours.
        $doc  = $node->ownerDocument;
        $all  = $doc->getElementsByTagName( '*' );
        foreach ( $all as $i => $n ) {
            if ( $n === $node ) {
                $pos = $i;
                break;
            }
        }
        $cache[ $id ] = $pos;
        return $pos;
    }
}
