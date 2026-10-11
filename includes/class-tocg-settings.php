<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class TOCG_Settings {

    public static function init(): void {
        add_action( 'admin_menu', [ self::class, 'add_page' ] );
        add_action( 'admin_init', [ self::class, 'register_settings' ] );
    }

    public static function add_page(): void {
        add_options_page(
            __( 'Table of Contents Generator', 'table-of-contents-generator' ),
            __( 'Table of Contents', 'table-of-contents-generator' ),
            'manage_options',
            'table-of-contents-generator',
            [ self::class, 'render_page' ]
        );
    }

    public static function register_settings(): void {
        register_setting(
            'tocg_settings_group',
            TOCG_OPTIONS_KEY,
            [ 'sanitize_callback' => [ self::class, 'sanitize' ] ]
        );

        add_settings_section( 'tocg_main', __( 'Settings', 'table-of-contents-generator' ), '__return_null', 'tocg_settings_group' );

        $fields = [
            'title'        => __( 'TOC Title', 'table-of-contents-generator' ),
            'min_headings' => __( 'Minimum Headings to Show TOC', 'table-of-contents-generator' ),
            'include_h3'   => __( 'Include H3 Headings', 'table-of-contents-generator' ),
            'post_types'   => __( 'Post Types', 'table-of-contents-generator' ),
        ];
        foreach ( $fields as $id => $label ) {
            add_settings_field( 'tocg_' . $id, $label, [ self::class, 'render_field' ], 'tocg_settings_group', 'tocg_main', [ 'field' => $id ] );
        }
    }

    public static function render_field( array $args ): void {
        $options = wp_parse_args( get_option( TOCG_OPTIONS_KEY, [] ), self::defaults() );
        $field   = $args['field'];
        $value   = $options[ $field ] ?? '';
        $name    = TOCG_OPTIONS_KEY . '[' . esc_attr( $field ) . ']';

        switch ( $field ) {
            case 'title':
                printf( '<input type="text" name="%s" value="%s" class="regular-text">', esc_attr( $name ), esc_attr( $value ) );
                break;

            case 'min_headings':
                printf(
                    '<input type="number" name="%s" value="%s" min="1" max="20" class="small-text">
                    <p class="description">%s</p>',
                    esc_attr( $name ),
                    esc_attr( $value ),
                    esc_html__( 'The TOC will only appear when the post has at least this many qualifying headings.', 'table-of-contents-generator' )
                );
                break;

            case 'include_h3':
                printf(
                    '<label><input type="checkbox" name="%s" value="1"%s> %s</label>',
                    esc_attr( $name ),
                    checked( $value, true, false ),
                    esc_html__( 'Include H3 headings as sub-items under H2', 'table-of-contents-generator' )
                );
                break;

            case 'post_types':
                $public_types = get_post_types( [ 'public' => true ], 'objects' );
                $selected     = (array) $value;
                foreach ( $public_types as $pt ) {
                    printf(
                        '<label style="display:block;margin-bottom:4px"><input type="checkbox" name="%s[]" value="%s"%s> %s</label>',
                        esc_attr( TOCG_OPTIONS_KEY . '[post_types]' ),
                        esc_attr( $pt->name ),
                        checked( in_array( $pt->name, $selected, true ), true, false ),
                        esc_html( $pt->label )
                    );
                }
                break;
        }
    }

    public static function sanitize( mixed $input ): array {
        $d            = self::defaults();
        $public_types = array_keys( get_post_types( [ 'public' => true ] ) );

        $post_types = [];
        if ( ! empty( $input['post_types'] ) && is_array( $input['post_types'] ) ) {
            foreach ( $input['post_types'] as $pt ) {
                if ( in_array( $pt, $public_types, true ) ) {
                    $post_types[] = $pt;
                }
            }
        }

        return [
            'title'        => isset( $input['title'] )        ? sanitize_text_field( $input['title'] )        : $d['title'],
            'min_headings' => isset( $input['min_headings'] )  ? max( 1, absint( $input['min_headings'] ) )    : $d['min_headings'],
            'include_h3'   => ! empty( $input['include_h3'] ),
            'post_types'   => empty( $post_types )             ? $d['post_types']                              : $post_types,
        ];
    }

    public static function render_page(): void {
        if ( ! current_user_can( 'manage_options' ) ) { return; }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields( 'tocg_settings_group' );
                do_settings_sections( 'tocg_settings_group' );
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public static function defaults(): array {
        return [
            'title'        => 'Table of Contents',
            'min_headings' => 3,
            'include_h3'   => true,
            'post_types'   => [ 'post', 'page' ],
        ];
    }
}
