<?php
/**
 * Plugin Name:       Tabaix llms.txt Auto Update
 * Plugin URI:        https://tabaix.com/llms.txt
 * Description:       Regenerates llms.txt at the site root whenever a post is published, updated, trashed, untrashed or deleted. Keeps the hand-written header (intro, key pages, tools, products) and rebuilds the "Latest guides" (25 newest published posts) and "Coupons" sections automatically.
 * Version:           1.0.1
 * Author:            Tabaix Enterprises
 * Author URI:        https://tabaix.com
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       tabaix-llms-auto-update
 */

/* Changelog 1.0.1: the Coupons section now reads the live coupon list
 * from the Tabaix Coupons Sync plugin (filter 'tabaix_coupons_list') when
 * that plugin is active, and falls back to the hardcoded list below when
 * it is not. Nothing else changed. */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'TABAIX_LLMS_FILE', ABSPATH . 'llms.txt' );
define( 'TABAIX_LLMS_OPTION_LAST_RUN', 'tabaix_llms_last_regenerated' );

/**
 * The hand-written header of llms.txt, preserved exactly as it was on
 * 2026-11-11 (intro, Key pages, Free tools by category, Tabaix products).
 * The plugin never rewrites this part — it only rebuilds the sections below it.
 *
 * @return string Static header, ending with a single newline.
 */
function tabaix_llms_static_header() {
	return <<<'TABAIX_LLMS_HEADER'
# Tabaix

> Tabaix is a free tools and publishing platform by Tabaix Enterprises. It offers 100+ free browser-based tools (image, PDF, video, text, developer, security, calculators, audio, SEO and design) that run locally in the visitor's browser, plus honest, experience-based guides and reviews with a focus on life in Saudi Arabia — Umrah travel, heat-tested gadgets, home water, and buying guides priced for Amazon.sa.

## Key pages

- [Home](https://tabaix.com/): Tabaix — free online tools, guides and honest reviews.
- [Free Tools](https://tabaix.com/tools/): The full tools hub — every free tool category in one place.
- [Blog and Guides](https://tabaix.com/posts-page/): All Tabaix articles, reviews and buying guides.
- [Learn](https://tabaix.com/learn/): Learning resources and explainers.
- [Shop](https://tabaix.com/shop/): Recommended products and deals.
- [Deals](https://tabaix.com/deals/): Tabaix deals and coupons hub on the main site.
- [Coupons](https://tabaix.com/coupons/): Coupon codes that actually work — Noon NDB221, Mumzworld CKSA26, Natural Touch D38, checked for Saudi Arabia & GCC.
- [Agency](https://tabaix.com/agency/): Tabaix Enterprises agency services — WordPress, technical SEO and e-commerce.
- [Najdi Art](https://tabaix.com/najdi-art/): Najdi Art services and workshop posts on Tabaix.
- [About Tayyab Ali and Tabaix](https://tabaix.com/about-tayyab-ali-tabaix/): Who runs Tabaix and why.
- [Contact](https://tabaix.com/contact-us/): Get in touch with Tabaix.
- [Terms](https://tabaix.com/terms/): Terms of use.

## Free tools by category

- [Image Tools](https://images.tabaix.com/en/): Compress, convert, resize and edit images in the browser.
- [PDF Tools](https://pdf.tabaix.com/): Merge, split, compress and convert PDF files.
- [Video Tools](https://video.tabaix.com/): Browser-based video utilities.
- [Text Tools](https://text.tabaix.com/): Counters, converters and text utilities.
- [Developer Tools](https://dev.tabaix.com/): Formatters, encoders and generators for developers.
- [Security Tools](https://security.tabaix.com/): Password and security utilities that run locally.
- [Calculators](https://calc.tabaix.com/): Everyday calculators.
- [Audio Tools](https://audio.tabaix.com/): Browser-based audio utilities.
- [SEO Tools](https://seo.tabaix.com/): Small SEO utilities.
- [Design Tools](https://design.tabaix.com/): Simple design helpers.

## Tabaix products

- [ImageTight](https://imagetight.com/): Free image compression for up to 1,000 images at once, no sign-up, files never leave the browser.
- [RemoveBG by Tabaix](https://remove.bg.tabaix.com/): Free background remover that runs on-device in the browser.
- [TABAIX Deals](https://deals.tabaix.com): Deals and coupons site with verified, joined affiliate offers.
- [Najdi Art](https://najdiart.tabaix.com): Najdi Art studio site.
- [TABAIX Keywords](https://keywords.tabaix.com): Keywords, FAQ ideas and SEO opportunities tool.
TABAIX_LLMS_HEADER;
}

/**
 * Active coupon codes, one line each in the generated file.
 * No rates here — those are internal only and never public.
 * Change the codes here (or filter 'tabaix_llms_coupons') and regenerate.
 *
 * @return array[] Each item: name, url, code, offer.
 */
function tabaix_llms_default_coupons() {
	return array(
		array(
			'name'  => 'Hostinger',
			'url'   => 'https://www.hostinger.com',
			'code'  => 'TABAIX',
			'offer' => '20% off the first term of a new hosting plan (via the referral link, not on renewals)',
		),
		array(
			'name'  => 'Noon',
			'url'   => 'https://www.noon.com',
			'code'  => 'NDB221',
			'offer' => 'Up to 10% cashback on eligible Noon orders (Saudi Arabia & UAE)',
		),
		array(
			'name'  => 'Mumzworld',
			'url'   => 'https://www.mumzworld.com',
			'code'  => 'CKSA26',
			'offer' => '10% off your order (up to SAR/AED 40 off)',
		),
		array(
			'name'  => 'Natural Touch',
			'url'   => 'https://www.natural-touch.com',
			'code'  => 'D38',
			'offer' => '10% off (Saudi Arabia)',
		),
		// PENDING — add once live on /coupons/: Namshi NMS208 (5% cashback sitewide), H&M DBKQ (10% off full price items).
	);
}

/**
 * Coupon list, filterable by other code.
 *
 * When Tabaix Coupons Sync is active, the Coupons section comes from its
 * single source of truth (filter 'tabaix_coupons_list', returning
 * brand/offer/code/url). When it is not active, the hardcoded list below
 * is used instead.
 *
 * @return array[]
 */
function tabaix_llms_coupons() {
	$sync = apply_filters( 'tabaix_coupons_list', array() );

	if ( is_array( $sync ) && ! empty( $sync ) ) {
		$mapped = array();
		foreach ( $sync as $coupon ) {
			if ( empty( $coupon['brand'] ) || empty( $coupon['code'] ) ) {
				continue;
			}
			$mapped[] = array(
				'name'  => $coupon['brand'],
				'url'   => ! empty( $coupon['url'] ) ? $coupon['url'] : 'https://tabaix.com/coupons/',
				'code'  => $coupon['code'],
				'offer' => ! empty( $coupon['offer'] ) ? $coupon['offer'] : '',
			);
		}
		if ( ! empty( $mapped ) ) {
			return apply_filters( 'tabaix_llms_coupons', $mapped );
		}
	}

	return apply_filters( 'tabaix_llms_coupons', tabaix_llms_default_coupons() );
}

/**
 * Build the full llms.txt contents: static header + generated sections.
 *
 * @return string
 */
function tabaix_llms_build_content() {
	$posts = get_posts(
		array(
			'post_type'              => 'post',
			'post_status'            => 'publish',
			'posts_per_page'         => 25,
			'orderby'                => 'date',
			'order'                  => 'DESC',
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		)
	);

	$lines = array();
	foreach ( $posts as $post ) {
		$title = wp_strip_all_tags( get_the_title( $post ) );
		// Keep markdown link syntax safe: no brackets, no line breaks.
		$title = str_replace( array( "\r", "\n", '[', ']', '(', ')' ), ' ', $title );
		$title = trim( preg_replace( '/\s+/', ' ', $title ) );
		if ( '' === $title ) {
			continue;
		}
		$lines[] = '- [' . $title . '](' . esc_url( get_permalink( $post ) ) . ')';
	}

	$content  = tabaix_llms_static_header();
	$content .= "\n## Latest guides\n\n";
	$content .= $lines ? implode( "\n", $lines ) . "\n" : "_No published guides yet._\n";
	$content .= "\n## Coupons\n\n";
	$content .= "Coupon codes assigned to TABAIX by the stores themselves — copy a code and paste it at checkout. See the [Deals hub](https://tabaix.com/deals/) on tabaix.com or the [Coupon codes page](https://deals.tabaix.com/coupons) on TABAIX Deals.\n\n";

	foreach ( tabaix_llms_coupons() as $coupon ) {
		$content .= '- [' . esc_html( $coupon['name'] ) . '](' . esc_url( $coupon['url'] ) . ') — code **' . esc_html( $coupon['code'] ) . '**: ' . esc_html( $coupon['offer'] ) . "\n";
	}

	$content .= "\n## Optional\n\n";
	$content .= "- [Sitemap](https://tabaix.com/sitemap_index.xml): Full XML sitemap index for Tabaix.\n";

	return $content;
}

/**
 * Whether llms.txt (or the site root, if the file does not exist yet) is writable.
 *
 * @return bool
 */
function tabaix_llms_is_writable() {
	if ( file_exists( TABAIX_LLMS_FILE ) ) {
		return is_writable( TABAIX_LLMS_FILE );
	}
	return is_writable( ABSPATH );
}

/**
 * Regenerate llms.txt now.
 *
 * @return true|WP_Error True on success, WP_Error when the file cannot be written.
 */
function tabaix_llms_regenerate() {
	if ( ! tabaix_llms_is_writable() ) {
		return new WP_Error(
			'tabaix_llms_not_writable',
			__( 'llms.txt is not writable. Check the file permissions on the site root.', 'tabaix-llms-auto-update' )
		);
	}

	$written = file_put_contents( TABAIX_LLMS_FILE, tabaix_llms_build_content(), LOCK_EX );

	if ( false === $written ) {
		return new WP_Error(
			'tabaix_llms_write_failed',
			__( 'Could not write llms.txt.', 'tabaix-llms-auto-update' )
		);
	}

	update_option( TABAIX_LLMS_OPTION_LAST_RUN, current_time( 'mysql' ) );

	return true;
}

/**
 * Regenerate on publish, update, trash and untrash of a post.
 * transition_post_status fires on every save, including publish -> publish.
 */
function tabaix_llms_on_post_status_change( $new_status, $old_status, $post ) {
	if ( ! $post instanceof WP_Post || 'post' !== $post->post_type ) {
		return;
	}
	if ( 'publish' === $new_status || 'publish' === $old_status ) {
		tabaix_llms_regenerate();
	}
}
add_action( 'transition_post_status', 'tabaix_llms_on_post_status_change', 10, 3 );

/**
 * Regenerate when a post is permanently deleted, so it drops out of the list.
 */
function tabaix_llms_on_post_deleted( $post_id, $post ) {
	if ( $post instanceof WP_Post && 'post' === $post->post_type ) {
		tabaix_llms_regenerate();
	}
}
add_action( 'deleted_post', 'tabaix_llms_on_post_deleted', 10, 2 );

/**
 * Build the file once on activation so it exists immediately.
 */
function tabaix_llms_activate() {
	tabaix_llms_regenerate();
}
register_activation_hook( __FILE__, 'tabaix_llms_activate' );

/**
 * Settings > llms.txt menu page.
 */
function tabaix_llms_admin_menu() {
	add_options_page(
		__( 'llms.txt', 'tabaix-llms-auto-update' ),
		__( 'llms.txt', 'tabaix-llms-auto-update' ),
		'manage_options',
		'tabaix-llms',
		'tabaix_llms_settings_page'
	);
}
add_action( 'admin_menu', 'tabaix_llms_admin_menu' );

/**
 * Handle the "Regenerate now" button: admin-only, nonce-checked, then redirect.
 */
function tabaix_llms_handle_regenerate() {
	if ( ! isset( $_POST['tabaix_llms_regenerate'] ) ) {
		return;
	}
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to do that.', 'tabaix-llms-auto-update' ) );
	}
	check_admin_referer( 'tabaix_llms_regenerate_action' );

	$result = tabaix_llms_regenerate();

	wp_safe_redirect(
		add_query_arg(
			array(
				'page'                => 'tabaix-llms',
				'tabaix_llms_result'  => is_wp_error( $result ) ? 'error' : 'ok',
			),
			admin_url( 'options-general.php' )
		)
	);
	exit;
}
add_action( 'admin_init', 'tabaix_llms_handle_regenerate' );

/**
 * The settings page: status, last run, and the Regenerate now button.
 */
function tabaix_llms_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to do that.', 'tabaix-llms-auto-update' ) );
	}

	$last_run  = get_option( TABAIX_LLMS_OPTION_LAST_RUN );
	$writable  = tabaix_llms_is_writable();
	$exists    = file_exists( TABAIX_LLMS_FILE );
	$result    = isset( $_GET['tabaix_llms_result'] ) ? sanitize_key( wp_unslash( $_GET['tabaix_llms_result'] ) ) : '';
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'llms.txt', 'tabaix-llms-auto-update' ); ?></h1>

		<?php if ( 'ok' === $result ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'llms.txt regenerated.', 'tabaix-llms-auto-update' ); ?></p></div>
		<?php elseif ( 'error' === $result ) : ?>
			<div class="notice notice-error is-dismissible"><p><?php esc_html_e( 'llms.txt could not be regenerated — the file is not writable. Fix the permissions on the site root and try again.', 'tabaix-llms-auto-update' ); ?></p></div>
		<?php endif; ?>

		<p><?php esc_html_e( 'This plugin rebuilds llms.txt at the site root whenever a post is published, updated, trashed, untrashed or deleted. The hand-written header stays untouched; only the "Latest guides" and "Coupons" sections are regenerated.', 'tabaix-llms-auto-update' ); ?></p>

		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'File', 'tabaix-llms-auto-update' ); ?></th>
				<td><code><?php echo esc_html( TABAIX_LLMS_FILE ); ?></code></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Status', 'tabaix-llms-auto-update' ); ?></th>
				<td>
					<?php if ( $exists ) : ?>
						<?php esc_html_e( 'Exists', 'tabaix-llms-auto-update' ); ?>
					<?php else : ?>
						<?php esc_html_e( 'Not created yet — regenerate below.', 'tabaix-llms-auto-update' ); ?>
					<?php endif; ?>
					<?php echo $writable ? ' · ' . esc_html__( 'writable', 'tabaix-llms-auto-update' ) : ' · ' . esc_html__( 'NOT writable', 'tabaix-llms-auto-update' ); ?>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Last regenerated', 'tabaix-llms-auto-update' ); ?></th>
				<td><?php echo $last_run ? esc_html( $last_run ) : esc_html__( 'Never', 'tabaix-llms-auto-update' ); ?></td>
			</tr>
		</table>

		<form method="post" action="<?php echo esc_url( admin_url( 'options-general.php?page=tabaix-llms' ) ); ?>">
			<?php wp_nonce_field( 'tabaix_llms_regenerate_action' ); ?>
			<?php submit_button( __( 'Regenerate now', 'tabaix-llms-auto-update' ), 'primary', 'tabaix_llms_regenerate' ); ?>
		</form>
	</div>
	<?php
}

/**
 * Clear admin notice when llms.txt cannot be written — never fail silently.
 */
function tabaix_llms_writable_notice() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	if ( tabaix_llms_is_writable() ) {
		return;
	}
	?>
	<div class="notice notice-error">
		<p>
			<?php
			printf(
				/* translators: %s: full path to llms.txt */
				esc_html__( 'Tabaix llms.txt Auto Update: %s is not writable, so llms.txt cannot be regenerated. Fix the file permissions on the site root.', 'tabaix-llms-auto-update' ),
				'<code>' . esc_html( TABAIX_LLMS_FILE ) . '</code>'
			);
			?>
		</p>
	</div>
	<?php
}
add_action( 'admin_notices', 'tabaix_llms_writable_notice' );
