<?php
/**
 * Plugin Name: Dig Free
 * Description: Publish vinyl listening history from Dig Free to your WordPress site.
 * Version:     2.0.0
 * Requires at least: 5.6
 * Author:      Dig Free
 */

defined( 'ABSPATH' ) || exit;

// ── CUSTOM POST TYPE ─────────────────────────────────────────────────────────

add_action( 'init', function () {

    register_post_type( 'digfree_pick', [
        'labels'       => [
            'name'               => 'Listening Picks',
            'singular_name'      => 'Listening Pick',
            'add_new_item'       => 'Add New Pick',
            'edit_item'          => 'Edit Pick',
            'view_item'          => 'View Pick',
            'search_items'       => 'Search Picks',
            'not_found'          => 'No picks found.',
            'not_found_in_trash' => 'No picks found in Trash.',
        ],
        'public'              => false,
        'show_ui'             => true,
        'show_in_rest'        => true,
        'show_in_nav_menus'   => false,
        'supports'            => [ 'title', 'custom-fields' ],
        'menu_icon'           => 'dashicons-format-audio',
        'menu_position'       => 30,
    ] );

    // String meta fields
    foreach ( [ 'artist', 'title', 'year', 'label', 'country', 'artwork_url', 'discogs_url', 'pick_date', 'note', 'source', 'digfree_id' ] as $field ) {
        register_post_meta( 'digfree_pick', '_digfree_' . $field, [
            'show_in_rest'  => true,
            'single'        => true,
            'type'          => 'string',
            'auth_callback' => '__return_true',
        ] );
    }

    // Array meta fields
    foreach ( [ 'genres', 'styles' ] as $field ) {
        register_post_meta( 'digfree_pick', '_digfree_' . $field, [
            'show_in_rest'  => [
                'schema' => [ 'type' => 'array', 'items' => [ 'type' => 'string' ] ],
            ],
            'single'        => true,
            'type'          => 'array',
            'auth_callback' => '__return_true',
        ] );
    }
} );

// ── REST API ─────────────────────────────────────────────────────────────────

add_action( 'rest_api_init', function () {

    register_rest_route( 'digfree/v1', '/entries', [
        [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => 'digfree_list_entries',
            'permission_callback' => fn() => current_user_can( 'publish_posts' ),
        ],
        [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => 'digfree_create_entry',
            'permission_callback' => fn() => current_user_can( 'publish_posts' ),
        ],
    ] );

    register_rest_route( 'digfree/v1', '/entries/(?P<id>\d+)', [
        [
            'methods'             => WP_REST_Server::DELETABLE,
            'callback'            => 'digfree_delete_entry',
            'permission_callback' => fn() => current_user_can( 'delete_posts' ),
            'args'                => [ 'id' => [ 'required' => true, 'type' => 'integer' ] ],
        ],
    ] );
} );

/**
 * GET /wp-json/digfree/v1/entries
 * Returns all published picks ordered by pick date descending.
 */
function digfree_list_entries( WP_REST_Request $req ): WP_REST_Response {

    $posts = get_posts( [
        'post_type'      => 'digfree_pick',
        'post_status'    => 'publish',
        'numberposts'    => 500,
        'orderby'        => 'meta_value',
        'meta_key'       => '_digfree_pick_date',
        'order'          => 'DESC',
    ] );

    $entries = array_map( fn( $p ) => [
        'id'          => $p->ID,
        'artist'      => get_post_meta( $p->ID, '_digfree_artist',      true ),
        'title'       => get_post_meta( $p->ID, '_digfree_title',       true ),
        'year'        => get_post_meta( $p->ID, '_digfree_year',        true ),
        'genres'      => (array) ( get_post_meta( $p->ID, '_digfree_genres', true ) ?: [] ),
        'styles'      => (array) ( get_post_meta( $p->ID, '_digfree_styles', true ) ?: [] ),
        'label'       => get_post_meta( $p->ID, '_digfree_label',       true ),
        'country'     => get_post_meta( $p->ID, '_digfree_country',     true ),
        'artwork_url' => get_post_meta( $p->ID, '_digfree_artwork_url', true ),
        'discogs_url' => get_post_meta( $p->ID, '_digfree_discogs_url', true ),
        'pick_date'   => get_post_meta( $p->ID, '_digfree_pick_date',   true ),
        'note'        => get_post_meta( $p->ID, '_digfree_note',        true ),
        'source'      => get_post_meta( $p->ID, '_digfree_source',      true ),
        'digfree_id'  => get_post_meta( $p->ID, '_digfree_digfree_id',  true ),
    ], $posts );

    return new WP_REST_Response( $entries, 200 );
}

/**
 * POST /wp-json/digfree/v1/entries
 * Creates a new pick entry. Idempotent — same digfree_id returns existing post.
 */
function digfree_create_entry( WP_REST_Request $req ): WP_REST_Response|WP_Error {

    $data = $req->get_json_params();

    // Accept both digfree_id (v2) and cratedig_id (v1 compat)
    $digfree_id = sanitize_text_field( $data['digfree_id'] ?? $data['cratedig_id'] ?? '' );
    if ( $digfree_id ) {
        $existing = get_posts( [
            'post_type'      => 'digfree_pick',
            'post_status'    => 'publish',
            'numberposts'    => 1,
            'meta_key'       => '_digfree_digfree_id',
            'meta_value'     => $digfree_id,
        ] );
        if ( $existing ) {
            return new WP_REST_Response( [ 'id' => $existing[0]->ID ], 200 );
        }
    }

    $artist = sanitize_text_field( $data['artist'] ?? '' );
    $title  = sanitize_text_field( $data['title']  ?? '' );
    $year   = sanitize_text_field( $data['year']   ?? '' );

    $post_id = wp_insert_post( [
        'post_type'   => 'digfree_pick',
        'post_status' => 'publish',
        'post_title'  => trim( $artist . ' — ' . $title . ( $year ? " ($year)" : '' ) ),
    ] );

    if ( is_wp_error( $post_id ) ) {
        return $post_id;
    }

    foreach ( [ 'artist', 'title', 'year', 'label', 'country', 'artwork_url', 'discogs_url', 'pick_date', 'note', 'source' ] as $field ) {
        if ( isset( $data[ $field ] ) ) {
            update_post_meta( $post_id, '_digfree_' . $field, sanitize_text_field( (string) $data[ $field ] ) );
        }
    }

    // Store the unique ID (from digfree_id or legacy cratedig_id)
    if ( $digfree_id ) {
        update_post_meta( $post_id, '_digfree_digfree_id', $digfree_id );
    }

    foreach ( [ 'genres', 'styles' ] as $field ) {
        if ( isset( $data[ $field ] ) && is_array( $data[ $field ] ) ) {
            update_post_meta( $post_id, '_digfree_' . $field, array_map( 'sanitize_text_field', $data[ $field ] ) );
        }
    }

    return new WP_REST_Response( [ 'id' => $post_id ], 201 );
}

/**
 * DELETE /wp-json/digfree/v1/entries/{id}
 * Permanently removes a pick by its WordPress post ID.
 */
function digfree_delete_entry( WP_REST_Request $req ): WP_REST_Response|WP_Error {

    $id   = (int) $req->get_param( 'id' );
    $post = get_post( $id );

    if ( ! $post || $post->post_type !== 'digfree_pick' ) {
        return new WP_Error( 'not_found', 'Entry not found.', [ 'status' => 404 ] );
    }

    $result = wp_delete_post( $id, true );

    return $result
        ? new WP_REST_Response( [ 'deleted' => true ], 200 )
        : new WP_Error( 'delete_failed', 'Could not delete entry.', [ 'status' => 500 ] );
}

// ── SHORTCODE [digfree_history] ──────────────────────────────────────────────

/**
 * Builds a pagination nav bar.
 */
function digfree_pagination( int $current, int $total, string $param ): string {
    if ( $total <= 1 ) return '';

    $show = [];
    for ( $i = 1; $i <= $total; $i++ ) {
        if ( $i === 1 || $i === $total || abs( $i - $current ) <= 2 ) {
            $show[] = $i;
        }
    }

    $url = fn( int $p ) => esc_url( add_query_arg( $param, $p ) );

    $out = '<div class="digfree-pagination">';

    $out .= $current > 1
        ? '<a class="digfree-page-btn" href="' . $url( $current - 1 ) . '">‹ Prev</a>'
        : '<span class="digfree-page-btn disabled">‹ Prev</span>';

    $prev = null;
    foreach ( $show as $p ) {
        if ( $prev !== null && $p - $prev > 1 ) {
            $out .= '<span class="digfree-page-btn ellipsis">…</span>';
        }
        $out .= $p === $current
            ? '<span class="digfree-page-btn current">' . $p . '</span>'
            : '<a class="digfree-page-btn" href="' . $url( $p ) . '">' . $p . '</a>';
        $prev = $p;
    }

    $out .= $current < $total
        ? '<a class="digfree-page-btn" href="' . $url( $current + 1 ) . '">Next ›</a>'
        : '<span class="digfree-page-btn disabled">Next ›</span>';

    $out .= '</div>';
    return $out;
}

add_shortcode( 'digfree_history', function ( $atts ) {

    $atts = shortcode_atts( [
        'limit'      => 100,
        'source'     => '',
        'year'       => '',
        'order'      => 'DESC',
        'offset'     => 0,
        'per_page'   => 0,
        'page_param' => 'digfree_page',
    ], $atts );

    $meta_query = [ 'relation' => 'AND' ];

    if ( in_array( $atts['source'], [ 'daily', 'user' ], true ) ) {
        $meta_query[] = [ 'key' => '_digfree_source', 'value' => $atts['source'] ];
    }

    $year_val = preg_replace( '/\D/', '', $atts['year'] );
    if ( strlen( $year_val ) === 4 ) {
        $meta_query[] = [
            'key'     => '_digfree_pick_date',
            'value'   => $year_val,
            'compare' => 'LIKE',
        ];
    }

    $order    = strtoupper( $atts['order'] ) === 'ASC' ? 'ASC' : 'DESC';
    $mq       = count( $meta_query ) > 1 ? $meta_query : '';
    $per_page = max( 0, (int) $atts['per_page'] );

    $pager_html = '';
    $pager_info = '';

    if ( $per_page > 0 ) {

        $page_param   = sanitize_key( $atts['page_param'] ?: 'digfree_page' );
        $current_page = max( 1, absint( $_GET[ $page_param ] ?? 1 ) );

        $q = new WP_Query( [
            'post_type'      => 'digfree_pick',
            'post_status'    => 'publish',
            'posts_per_page' => $per_page,
            'paged'          => $current_page,
            'meta_key'       => '_digfree_pick_date',
            'orderby'        => 'meta_value',
            'order'          => $order,
            'meta_query'     => $mq,
        ] );

        $posts        = $q->posts;
        $total_posts  = (int) $q->found_posts;
        $total_pages  = (int) $q->max_num_pages;
        $current_page = min( $current_page, max( 1, $total_pages ) );

        if ( $total_posts > 0 ) {
            $from       = ( $current_page - 1 ) * $per_page + 1;
            $to         = min( $current_page * $per_page, $total_posts );
            $pager_info = sprintf(
                'Showing %d–%d of %d pick%s · Page %d of %d',
                $from, $to, $total_posts,
                $total_posts === 1 ? '' : 's',
                $current_page, $total_pages
            );
            $pager_html = digfree_pagination( $current_page, $total_pages, $page_param );
        }

    } else {

        $posts = get_posts( [
            'post_type'      => 'digfree_pick',
            'post_status'    => 'publish',
            'numberposts'    => (int) $atts['limit'],
            'offset'         => max( 0, (int) $atts['offset'] ),
            'orderby'        => 'meta_value',
            'meta_key'       => '_digfree_pick_date',
            'order'          => $order,
            'meta_query'     => $mq,
        ] );

    }

    if ( ! $posts ) {
        return '<p class="digfree-empty" style="color:#999;font-style:italic;">No listening history published yet.</p>';
    }

    static $styles_printed = false;

    ob_start();

    if ( ! $styles_printed ) :
        $styles_printed = true; ?>
    <style>
      .digfree-history { font-family: system-ui, -apple-system, sans-serif; }

      .digfree-pick {
        display: flex;
        flex-wrap: wrap;
        gap: 18px;
        padding: 20px 0;
        border-bottom: 1px solid #e8e8e8;
        align-items: flex-start;
      }
      .digfree-pick:last-child { border-bottom: none; }

      .digfree-art {
        flex-shrink: 0;
        width: 96px; height: 96px;
        border-radius: 8px;
        overflow: hidden;
        background: #f2f2f2;
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
      }
      .digfree-art img {
        display: block !important;
        width: 96px !important;
        height: 96px !important;
        object-fit: cover !important;
        max-width: none !important;
        margin: 0 !important;
        padding: 0 !important;
        vertical-align: top !important;
      }
      .digfree-art-placeholder { font-size: 36px; color: #ccc; }

      .digfree-info { flex: 1; min-width: 0; }
      .digfree-artist { font-weight: 700; font-size: 1.05rem; margin-bottom: 3px; }
      .digfree-title {
        font-style: italic;
        color: #777;
        font-size: 0.95rem;
        margin-bottom: 8px;
        overflow: hidden;
        text-overflow: ellipsis;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
      }
      .digfree-tags { display: flex; flex-wrap: wrap; gap: 5px; margin-bottom: 6px; }
      .digfree-tag {
        font-size: 0.72rem;
        background: #f0f0f0;
        padding: 3px 9px;
        border-radius: 10px;
        color: #555;
        text-transform: uppercase;
        letter-spacing: .05em;
      }
      .digfree-tag.year { background: #fff3e0; color: #d4830a; }
      .digfree-sub       { font-size: 0.78rem; color: #999; margin-bottom: 5px; }
      .digfree-picked-on { font-size: 0.78rem; color: #999; margin-bottom: 5px; }
      .digfree-source {
        display: inline-block;
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: .08em;
        padding: 3px 10px;
        border-radius: 10px;
      }
      .digfree-source.daily { color: #c8922a; background: rgba(200,146,42,.1); }
      .digfree-source.user  { color: #4a8c5c; background: rgba(74,140,92,.12); }

      a.digfree-art-link {
        display: block;
        position: absolute;
        inset: 0;
        z-index: 1;
      }
      a.digfree-art-link::after {
        content: '';
        position: absolute;
        inset: 0;
        background: rgba(0,0,0,0.12);
        opacity: 0;
        transition: opacity 0.2s;
      }
      a.digfree-art-link:hover::after { opacity: 1; }
      a.digfree-title-link { color: inherit; text-decoration: none; }
      a.digfree-title-link:hover { color: #d4830a; text-decoration: underline; text-underline-offset: 3px; }

      .digfree-note-col {
        flex-shrink: 0;
        width: 210px;
        font-style: italic;
        color: #777;
        font-size: 0.9rem;
        line-height: 1.6;
        padding-left: 18px;
        border-left: 2px solid #efefef;
        align-self: center;
      }

      .digfree-pager {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 10px;
        padding: 12px 0;
        border-bottom: 1px solid #e8e8e8;
      }
      .digfree-pager.bottom {
        border-bottom: none;
        border-top: 1px solid #e8e8e8;
        padding-top: 16px;
        margin-top: 4px;
      }
      .digfree-pager-info { font-size: 0.78rem; color: #999; }
      .digfree-pagination { display: flex; flex-wrap: wrap; gap: 4px; }
      .digfree-page-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 36px;
        height: 36px;
        padding: 0 10px;
        border: 1px solid #e0e0e0;
        border-radius: 6px;
        font-size: 0.82rem;
        color: #555;
        text-decoration: none;
        background: #fff;
        line-height: 1;
        transition: border-color 0.15s, color 0.15s, background 0.15s;
      }
      a.digfree-page-btn:hover { border-color: #d4830a; color: #d4830a; background: rgba(212,131,10,.05); }
      .digfree-page-btn.current { background: #d4830a; color: #fff; border-color: #d4830a; font-weight: 700; }
      .digfree-page-btn.disabled { color: #ccc; border-color: #eee; background: #fafafa; cursor: default; }
      .digfree-page-btn.ellipsis { border-color: transparent; background: none; cursor: default; min-width: 20px; }

      @media (max-width: 640px) {
        .digfree-art { width: 80px; height: 80px; }
        .digfree-art img { width: 80px !important; height: 80px !important; }
        .digfree-note-col {
          width: 100%;
          border-left: none;
          padding-left: 0;
          padding-top: 10px;
          border-top: 1px solid #efefef;
          margin-top: 2px;
        }
        .digfree-pager { flex-direction: column; align-items: flex-start; }
      }

      @media (max-width: 400px) {
        .digfree-pick { gap: 12px; }
        .digfree-art { width: 68px; height: 68px; }
        .digfree-art img { width: 68px !important; height: 68px !important; }
        .digfree-artist { font-size: 0.98rem; }
        .digfree-title  { font-size: 0.88rem; }
        .digfree-page-btn { min-width: 30px; height: 30px; font-size: 0.76rem; padding: 0 7px; }
      }
    </style>
    <?php endif; ?>

    <?php if ( $pager_html ) : ?>
    <div class="digfree-pager" id="digfree-history-top">
      <div class="digfree-pager-info"><?php echo esc_html( $pager_info ); ?></div>
      <?php echo $pager_html; ?>
    </div>
    <?php endif; ?>

    <div class="digfree-history">
    <?php foreach ( $posts as $p ) :
        $artist      = get_post_meta( $p->ID, '_digfree_artist',      true );
        $title       = get_post_meta( $p->ID, '_digfree_title',       true );
        $year        = get_post_meta( $p->ID, '_digfree_year',        true );
        $label       = get_post_meta( $p->ID, '_digfree_label',       true );
        $country     = get_post_meta( $p->ID, '_digfree_country',     true );
        $artwork_url = get_post_meta( $p->ID, '_digfree_artwork_url', true );
        $discogs_url = get_post_meta( $p->ID, '_digfree_discogs_url', true );
        $pick_date   = get_post_meta( $p->ID, '_digfree_pick_date',   true );
        $note        = get_post_meta( $p->ID, '_digfree_note',        true );
        $source      = get_post_meta( $p->ID, '_digfree_source',      true ) ?: 'daily';
        $genres      = (array) ( get_post_meta( $p->ID, '_digfree_genres', true ) ?: [] );
        $styles      = (array) ( get_post_meta( $p->ID, '_digfree_styles', true ) ?: [] );
        $tags        = array_slice( array_merge( $genres, $styles ), 0, 4 );
        $ts          = $pick_date ? preg_replace( '/\.\d+/', '', $pick_date ) : '';
        $date_fmt    = $ts ? wp_date( 'M j, Y', strtotime( $ts ) ) : '';
        $sub_parts   = array_filter( [ $label, $country ] );
        $source_label = $source === 'daily' ? '🎵 Daily Pick' : '♥ My Pick';
    ?>
      <div class="digfree-pick">
        <div class="digfree-art">
          <?php if ( $artwork_url ) : ?>
            <img src="<?php echo esc_url( $artwork_url ); ?>" alt="<?php echo esc_attr( "$artist – $title" ); ?>" loading="lazy">
          <?php else : ?>
            <span class="digfree-art-placeholder">♫</span>
          <?php endif; ?>
          <?php if ( $discogs_url ) : ?>
            <a class="digfree-art-link" href="<?php echo esc_url( $discogs_url ); ?>" target="_blank" rel="noopener noreferrer" title="View on Discogs"></a>
          <?php endif; ?>
        </div>
        <div class="digfree-info">
          <div class="digfree-artist"><?php echo esc_html( $artist ); ?></div>
          <div class="digfree-title">
            <?php if ( $discogs_url ) : ?>
              <a class="digfree-title-link" href="<?php echo esc_url( $discogs_url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $title ); ?></a>
            <?php else : ?>
              <?php echo esc_html( $title ); ?>
            <?php endif; ?>
          </div>
          <div class="digfree-tags">
            <?php if ( $year ) : ?><span class="digfree-tag year"><?php echo esc_html( $year ); ?></span><?php endif; ?>
            <?php foreach ( $tags as $tag ) : ?><span class="digfree-tag"><?php echo esc_html( $tag ); ?></span><?php endforeach; ?>
          </div>
          <?php if ( $sub_parts ) : ?>
            <div class="digfree-sub"><?php echo esc_html( implode( ' · ', $sub_parts ) ); ?></div>
          <?php endif; ?>
          <?php if ( $date_fmt ) : ?>
            <div class="digfree-picked-on">Picked on: <?php echo esc_html( $date_fmt ); ?></div>
          <?php endif; ?>
          <span class="digfree-source <?php echo esc_attr( $source ); ?>"><?php echo esc_html( $source_label ); ?></span>
        </div>
        <?php if ( $note ) : ?>
        <div class="digfree-note-col"><?php echo esc_html( $note ); ?></div>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
    </div>

    <?php if ( $pager_html ) : ?>
    <div class="digfree-pager bottom">
      <div class="digfree-pager-info"><?php echo esc_html( $pager_info ); ?></div>
      <?php echo $pager_html; ?>
    </div>
    <?php endif; ?>

    <?php
    return ob_get_clean();
} );
