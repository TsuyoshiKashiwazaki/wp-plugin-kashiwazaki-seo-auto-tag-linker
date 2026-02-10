<?php
/**
 * Plugin Name: Kashiwazaki SEO Auto Tag Linker
 * Plugin URI: https://www.tsuyoshikashiwazaki.jp
 * Description: 投稿コンテンツ内のタグ名に一致するテキストを自動的にタグアーカイブページへのリンクに変換し、内部リンク構造を強化するSEOプラグインです。
 * Version: 1.0.0
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
define( 'KSATL_VERSION', '1.0.0' );
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
		'post_types'        => array( 'post' ),
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
	?>
	<style id="ksatl-auto-link-css">
	a.ksatl-auto-link{text-decoration:underline dashed!important;text-decoration-thickness:1px!important;text-underline-offset:3px;text-decoration-color:rgba(127,127,127,.5)!important}
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
