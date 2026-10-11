<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class TOCG_Output {

    public static function init(): void {
        // Priority 10 — runs before most content filters.
        add_filter( 'the_content', [ self::class, 'inject_toc' ], 10 );
        add_action( 'wp_enqueue_scripts', [ self::class, 'enqueue' ] );
    }

    public static function enqueue(): void {
        wp_enqueue_style(
            'tocg-front',
            TOCG_PLUGIN_URL . 'assets/css/front.css',
            [],
            TOCG_VERSION
        );
    }

    public static function inject_toc( string $content ): string {
        if ( ! is_singular() ) {
            return $content;
        }

        $options = wp_parse_args( get_option( TOCG_OPTIONS_KEY, [] ), TOCG_Settings::defaults() );

        // Check post type.
        $post_types = (array) ( $options['post_types'] ?? [ 'post', 'page' ] );
        if ( ! in_array( get_post_type(), $post_types, true ) ) {
            return $content;
        }

        // Build tag list from settings.
        $tags = [ 'h2' ];
        if ( ! empty( $options['include_h3'] ) ) {
            $tags[] = 'h3';
        }

        $headings = TOCG_Parser::extract( $content, $tags );

        // Respect minimum headings threshold.
        if ( count( $headings ) < absint( $options['min_headings'] ) ) {
            return $content;
        }

        // Inject id attributes into the content headings so anchor links resolve.
        $content = TOCG_Parser::inject_ids( $content, $headings );

        $toc = self::build_toc( $headings, $options['title'] );

        return $toc . $content;
    }

    /**
     * Build the TOC HTML from the extracted headings.
     *
     * @param array<int, array{tag: string, text: string, id: string}> $headings
     */
    private static function build_toc( array $headings, string $title ): string {
        $html  = '<nav class="tocg-toc" aria-label="' . esc_attr__( 'Table of Contents', 'table-of-contents-generator' ) . '">';

        if ( ! empty( $title ) ) {
            $html .= '<p class="tocg-title">' . esc_html( $title ) . '</p>';
        }

        $html .= '<ol class="tocg-list">';

        $in_sublist = false;

        foreach ( $headings as $heading ) {
            $link = '<a href="#' . esc_attr( $heading['id'] ) . '">' . esc_html( $heading['text'] ) . '</a>';

            if ( $heading['tag'] === 'h2' ) {
                if ( $in_sublist ) {
                    $html      .= '</ol></li>';
                    $in_sublist = false;
                }
                $html .= '<li>' . $link;
                // Peek ahead: if next heading is h3, we'll open a sublist then.
            } else {
                // h3 — open a sublist under the current h2 if not already open.
                if ( ! $in_sublist ) {
                    $html      .= '<ol class="tocg-sublist">';
                    $in_sublist = true;
                }
                $html .= '<li>' . $link . '</li>';
            }
        }

        if ( $in_sublist ) {
            $html .= '</ol></li>';
        } else {
            // Close the last h2 li if it had no h3 children.
            $html .= '</li>';
        }

        $html .= '</ol></nav>';

        return $html;
    }
}
