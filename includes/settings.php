<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * 管理メニューに設定ページを追加
 */
function ksatl_add_admin_menu() {
	add_menu_page(
		'Kashiwazaki SEO Auto Tag Linker 設定',
		'Kashiwazaki SEO Auto Tag Linker',
		'manage_options',
		'ksatl_settings_page',
		'ksatl_render_settings_page',
		'dashicons-tag',
		81
	);
}

/**
 * 設定APIを登録
 */
function ksatl_register_settings() {
	register_setting(
		KSATL_OPTION_NAME,
		KSATL_OPTION_NAME,
		'ksatl_sanitize_options'
	);

	add_settings_section(
		'ksatl_general_section',
		'一般設定',
		'__return_null',
		'ksatl_settings_page'
	);

	add_settings_field(
		'ksatl_enabled_field',
		'自動タグリンク機能',
		'ksatl_enabled_field_callback',
		'ksatl_settings_page',
		'ksatl_general_section'
	);

	add_settings_field(
		'ksatl_max_links_per_tag_field',
		'同じタグの最大リンク回数',
		'ksatl_max_links_per_tag_field_callback',
		'ksatl_settings_page',
		'ksatl_general_section'
	);

	add_settings_field(
		'ksatl_min_tag_length_field',
		'最小タグ文字数',
		'ksatl_min_tag_length_field_callback',
		'ksatl_settings_page',
		'ksatl_general_section'
	);

	add_settings_field(
		'ksatl_link_target_field',
		'リンクの開き方',
		'ksatl_link_target_field_callback',
		'ksatl_settings_page',
		'ksatl_general_section'
	);

	add_settings_field(
		'ksatl_excluded_tags_field',
		'除外タグ',
		'ksatl_excluded_tags_field_callback',
		'ksatl_settings_page',
		'ksatl_general_section'
	);

	add_settings_field(
		'ksatl_post_types_field',
		'対象投稿タイプ',
		'ksatl_post_types_field_callback',
		'ksatl_settings_page',
		'ksatl_general_section'
	);
}

/**
 * 設定ページのHTMLを描画
 */
function ksatl_render_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	?>
	<div class="wrap">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
		<form action="options.php" method="post">
			<?php
			settings_fields( KSATL_OPTION_NAME );
			do_settings_sections( 'ksatl_settings_page' );
			submit_button( '変更を保存' );
			?>
		</form>
	</div>
	<?php
}

// --- 各フィールドのコールバック関数 ---

/** 有効/無効チェックボックス */
function ksatl_enabled_field_callback() {
	$options = get_option( KSATL_OPTION_NAME, ksatl_get_default_options() );
	$enabled = isset( $options['enabled'] ) ? (bool) $options['enabled'] : true;
	?>
	<label for="ksatl_enabled">
		<input type="checkbox" id="ksatl_enabled" name="<?php echo esc_attr( KSATL_OPTION_NAME ); ?>[enabled]" value="1" <?php checked( $enabled, true ); ?>>
		自動タグリンク機能を有効にする
	</label>
	<?php
}

/** 同じタグの最大リンク回数フィールド */
function ksatl_max_links_per_tag_field_callback() {
	$options = get_option( KSATL_OPTION_NAME, ksatl_get_default_options() );
	$max_links_per_tag = isset( $options['max_links_per_tag'] ) ? absint( $options['max_links_per_tag'] ) : 1;
	?>
	<input type="number" id="ksatl_max_links_per_tag" name="<?php echo esc_attr( KSATL_OPTION_NAME ); ?>[max_links_per_tag]" value="<?php echo esc_attr( $max_links_per_tag ); ?>" min="1" max="10" class="small-text">
	<p class="description">同じタグ名が記事内に複数回出現する場合、リンクに変換する最大回数です（例: 1 = 初出のみ）。</p>
	<?php
}

/** 最小タグ文字数フィールド */
function ksatl_min_tag_length_field_callback() {
	$options = get_option( KSATL_OPTION_NAME, ksatl_get_default_options() );
	$min_length = isset( $options['min_tag_length'] ) ? absint( $options['min_tag_length'] ) : 3;
	?>
	<input type="number" id="ksatl_min_tag_length" name="<?php echo esc_attr( KSATL_OPTION_NAME ); ?>[min_tag_length]" value="<?php echo esc_attr( $min_length ); ?>" min="1" max="20" class="small-text">
	<p class="description">この文字数未満のタグは自動リンクの対象外になります。短いタグの誤マッチを防止します。</p>
	<?php
}

/** リンクの開き方フィールド */
function ksatl_link_target_field_callback() {
	$options = get_option( KSATL_OPTION_NAME, ksatl_get_default_options() );
	$target  = isset( $options['link_target'] ) ? $options['link_target'] : '_self';
	?>
	<select id="ksatl_link_target" name="<?php echo esc_attr( KSATL_OPTION_NAME ); ?>[link_target]">
		<option value="_self" <?php selected( $target, '_self' ); ?>>通常リンク（同じタブで開く）</option>
		<option value="_blank" <?php selected( $target, '_blank' ); ?>>新しいタブで開く</option>
	</select>
	<?php
}

/** 除外タグフィールド */
function ksatl_excluded_tags_field_callback() {
	$options = get_option( KSATL_OPTION_NAME, ksatl_get_default_options() );
	$excluded = isset( $options['excluded_tags'] ) ? $options['excluded_tags'] : '';
	?>
	<textarea id="ksatl_excluded_tags" name="<?php echo esc_attr( KSATL_OPTION_NAME ); ?>[excluded_tags]" rows="3" cols="50" class="large-text"><?php echo esc_textarea( $excluded ); ?></textarea>
	<p class="description">自動リンクの対象外にするタグ名をカンマ区切りで入力してください。例: タグA, タグB, タグC</p>
	<?php
}

/** 対象投稿タイプフィールド */
function ksatl_post_types_field_callback() {
	$options = get_option( KSATL_OPTION_NAME, ksatl_get_default_options() );
	$selected_types = isset( $options['post_types'] ) ? (array) $options['post_types'] : array( 'post' );

	$post_types = get_post_types( array( 'public' => true, 'show_ui' => true ), 'objects' );
	unset( $post_types['attachment'] );
	?>
	<p style="margin: 0 0 8px;">
		<button type="button" class="button button-small" id="ksatl-check-all">全チェック</button>
		<button type="button" class="button button-small" id="ksatl-uncheck-all">全解除</button>
	</p>
	<?php
	foreach ( $post_types as $post_type ) {
		$checked = in_array( $post_type->name, $selected_types, true );
		?>
		<label style="display: block; margin-bottom: 5px;">
			<input type="checkbox" class="ksatl-post-type-cb" name="<?php echo esc_attr( KSATL_OPTION_NAME ); ?>[post_types][]" value="<?php echo esc_attr( $post_type->name ); ?>" <?php checked( $checked, true ); ?>>
			<?php echo esc_html( $post_type->labels->name ); ?> (<code><?php echo esc_html( $post_type->name ); ?></code>)
		</label>
		<?php
	}
	?>
	<p class="description">自動タグリンクを適用する投稿タイプを選択してください。</p>
	<script>
	document.getElementById('ksatl-check-all').addEventListener('click', function() {
		document.querySelectorAll('.ksatl-post-type-cb').forEach(function(cb) { cb.checked = true; });
	});
	document.getElementById('ksatl-uncheck-all').addEventListener('click', function() {
		document.querySelectorAll('.ksatl-post-type-cb').forEach(function(cb) { cb.checked = false; });
	});
	</script>
	<?php
}

/**
 * オプション保存時のサニタイズ関数
 */
function ksatl_sanitize_options( $input ) {
	$new_input = array();
	$defaults = ksatl_get_default_options();

	// 有効/無効
	$new_input['enabled'] = ! empty( $input['enabled'] );

	// 同じタグの最大リンク回数
	$new_input['max_links_per_tag'] = isset( $input['max_links_per_tag'] )
		? max( 1, absint( $input['max_links_per_tag'] ) )
		: $defaults['max_links_per_tag'];

	// 最小タグ文字数
	$new_input['min_tag_length'] = isset( $input['min_tag_length'] )
		? max( 1, absint( $input['min_tag_length'] ) )
		: $defaults['min_tag_length'];

	// リンクの開き方
	$new_input['link_target'] = ( isset( $input['link_target'] ) && $input['link_target'] === '_blank' )
		? '_blank'
		: '_self';

	// 除外タグ
	$new_input['excluded_tags'] = isset( $input['excluded_tags'] )
		? wp_strip_all_tags( trim( $input['excluded_tags'] ) )
		: '';

	// 対象投稿タイプ
	if ( ! empty( $input['post_types'] ) && is_array( $input['post_types'] ) ) {
		$new_input['post_types'] = array_filter(
			array_map( 'sanitize_key', $input['post_types'] ),
			'post_type_exists'
		);
		$new_input['post_types'] = array_values( $new_input['post_types'] );
	}
	if ( empty( $new_input['post_types'] ) ) {
		$new_input['post_types'] = array( 'post' );
	}

	return $new_input;
}
