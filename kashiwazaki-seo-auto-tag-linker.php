<?php
/**
 * Plugin Name: Kashiwazaki SEO Auto Tag Linker
 * Plugin URI: https://www.tsuyoshikashiwazaki.jp
 * Description: 投稿コンテンツ内のタグ名に一致するテキストを自動的にタグアーカイブページへのリンクに変換し、内部リンク構造を強化するSEOプラグインです。
 * Version: 1.0.2
 * Author: 柏崎剛 (Tsuyoshi Kashiwazaki)
 * Author URI: https://www.tsuyoshikashiwazaki.jp/profile/
 * Text Domain: kashiwazaki-seo-auto-tag-linker
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // 直接アクセス禁止
}

// 定数
define( 'KSATL_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'KSATL_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'KSATL_VERSION', '1.0.2' );
define( 'KSATL_OPTION_NAME', 'ksatl_options' );
define( 'KSATL_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * 設定のデフォルト値を取得する関数
 */
function ksatl_get_default_options() {
	return array(
		'enabled'           => true,
		'max_links_per_tag' => 1,
		'link_target'       => '_self',
		'min_tag_length'    => 3,
		'excluded_tags'     => '',
		'exclude_self_tags' => true,
		'post_types'        => array( 'post' ),
		'link_color_mode'    => 'inherit',
		'link_color_custom'  => '#333333',
		'link_underline_style' => 'dashed',
	);
}

/**
 * プラグインが有効化されたときに実行される関数
 */
function ksatl_activate() {
	$current_options = get_option( KSATL_OPTION_NAME );
	$default_options = ksatl_get_default_options();

	if ( $current_options === false ) {
		add_option( KSATL_OPTION_NAME, $default_options );
	} else {
		$merged_options = wp_parse_args( $current_options, $default_options );
		if ( $merged_options !== $current_options ) {
			update_option( KSATL_OPTION_NAME, $merged_options );
		}
	}
}
register_activation_hook( __FILE__, 'ksatl_activate' );

/**
 * プラグインが無効化されたときに実行される関数
 */
function ksatl_deactivate() {
	// オプションは保持する
}
register_deactivation_hook( __FILE__, 'ksatl_deactivate' );

// 必要なファイルを読み込む
require_once KSATL_PLUGIN_DIR . 'includes/settings.php';
require_once KSATL_PLUGIN_DIR . 'includes/auto-linker.php';

// WordPressのアクションフックに関数を登録
add_action( 'admin_init', 'ksatl_register_settings' );
add_action( 'admin_menu', 'ksatl_add_admin_menu' );
add_filter( 'the_content', 'ksatl_auto_link_tags', 15 );
add_action( 'wp_head', 'ksatl_output_frontend_css' );

/**
 * フロントエンドにCSS を出力（自動タグリンクの破線スタイル）
 */
function ksatl_output_frontend_css() {
	$options    = get_option( KSATL_OPTION_NAME, ksatl_get_default_options() );
	$color_mode = isset( $options['link_color_mode'] ) ? $options['link_color_mode'] : 'inherit';
	$color_css  = 'inherit';

	if ( 'custom' === $color_mode && ! empty( $options['link_color_custom'] ) ) {
		$color_css = sanitize_hex_color( $options['link_color_custom'] );
		if ( ! $color_css ) {
			$color_css = 'inherit';
		}
	}
	$underline_style = isset( $options['link_underline_style'] ) ? $options['link_underline_style'] : 'dashed';
	$allowed_styles  = array( 'solid', 'dashed', 'dotted', 'double', 'wavy', 'none' );
	if ( ! in_array( $underline_style, $allowed_styles, true ) ) {
		$underline_style = 'dashed';
	}

	if ( 'none' === $underline_style ) {
		$decoration_css = 'text-decoration:none!important';
	} else {
		$decoration_css = 'text-decoration:underline ' . $underline_style . '!important;text-decoration-thickness:1px!important;text-underline-offset:3px;text-decoration-color:rgba(127,127,127,.5)!important';
	}
	?>
	<style id="ksatl-auto-link-css">
	a.ksatl-auto-link{color:<?php echo esc_attr( $color_css ); ?>!important;<?php echo $decoration_css; ?>}
	</style>
	<?php
}

// プラグイン一覧に設定リンクを追加
add_filter( 'plugin_action_links_' . KSATL_PLUGIN_BASENAME, 'ksatl_add_settings_link' );

/**
 * プラグイン一覧ページに設定リンクを追加
 */
function ksatl_add_settings_link( $links ) {
	$settings_link = '<a href="' . admin_url( 'admin.php?page=ksatl_settings_page' ) . '">設定</a>';
	array_unshift( $links, $settings_link );
	return $links;
}
