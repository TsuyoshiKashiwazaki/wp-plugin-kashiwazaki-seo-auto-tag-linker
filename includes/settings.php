<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * 管理メニューに設定ページを追加
 */
function ksatl_add_admin_menu() {
	$hook = add_menu_page(
		'Kashiwazaki SEO Auto Tag Linker 設定',
		'Kashiwazaki SEO Auto Tag Linker',
		'manage_options',
		'ksatl_settings_page',
		'ksatl_render_settings_page',
		'dashicons-tag',
		81
	);

	add_action( 'admin_print_scripts-' . $hook, 'ksatl_enqueue_color_picker' );
}

/**
 * 設定ページでカラーピッカーを読み込む
 */
function ksatl_enqueue_color_picker() {
	wp_enqueue_style( 'wp-color-picker' );
	wp_enqueue_script( 'wp-color-picker' );
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
		'ksatl_exclude_self_tags_field',
		'自記事タグの除外',
		'ksatl_exclude_self_tags_field_callback',
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

	add_settings_field(
		'ksatl_cache_duration_field',
		'タグキャッシュ保持時間',
		'ksatl_cache_duration_field_callback',
		'ksatl_settings_page',
		'ksatl_general_section'
	);

	// スタイル設定セクション
	add_settings_section(
		'ksatl_style_section',
		'スタイル設定',
		'__return_null',
		'ksatl_settings_page'
	);

	add_settings_field(
		'ksatl_link_color_mode_field',
		'リンクの文字色',
		'ksatl_link_color_mode_field_callback',
		'ksatl_settings_page',
		'ksatl_style_section'
	);

	add_settings_field(
		'ksatl_link_underline_style_field',
		'下線のスタイル',
		'ksatl_link_underline_style_field_callback',
		'ksatl_settings_page',
		'ksatl_style_section'
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
	<style>
	.ksatl-settings .postbox { margin-bottom: 20px; }
	.ksatl-settings .postbox .hndle { cursor: default; padding: 8px 12px; margin: 0; font-size: 14px; border-bottom: 1px solid #c3c4c7; }
	.ksatl-settings .postbox .inside { padding: 0 12px 12px; margin: 0; }
	.ksatl-settings .postbox .inside .form-table { margin-top: 0; }
	.ksatl-settings .postbox .inside .form-table th { padding-left: 0; }
	.ksatl-post-types-grid {
		display: grid;
		grid-template-columns: repeat(3, 1fr);
		gap: 4px 16px;
		max-height: 240px;
		overflow-y: auto;
		border: 1px solid #c3c4c7;
		border-radius: 4px;
		background: #f6f7f7;
		padding: 10px 12px;
	}
	.ksatl-post-types-grid label {
		display: flex;
		align-items: center;
		gap: 4px;
		white-space: nowrap;
		overflow: hidden;
		text-overflow: ellipsis;
	}
	</style>

	<div class="wrap ksatl-settings">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
		<form action="options.php" method="post">
			<?php settings_fields( KSATL_OPTION_NAME ); ?>

			<!-- 基本設定 -->
			<div class="postbox">
				<h2 class="hndle">基本設定</h2>
				<div class="inside">
					<table class="form-table"><tbody>
						<tr>
							<th scope="row">自動タグリンク機能</th>
							<td><?php ksatl_enabled_field_callback(); ?></td>
						</tr>
					<tr>
						<th scope="row">タグキャッシュ保持時間</th>
						<td><?php ksatl_cache_duration_field_callback(); ?></td>
					</tr>
					</tbody></table>
				</div>
			</div>

			<!-- リンク動作 -->
			<div class="postbox">
				<h2 class="hndle">リンク動作</h2>
				<div class="inside">
					<table class="form-table"><tbody>
						<tr>
							<th scope="row">同じタグの最大リンク回数</th>
							<td><?php ksatl_max_links_per_tag_field_callback(); ?></td>
						</tr>
						<tr>
							<th scope="row">最小タグ文字数</th>
							<td><?php ksatl_min_tag_length_field_callback(); ?></td>
						</tr>
						<tr>
							<th scope="row">リンクの開き方</th>
							<td><?php ksatl_link_target_field_callback(); ?></td>
						</tr>
						<tr>
							<th scope="row">自記事タグの除外</th>
							<td><?php ksatl_exclude_self_tags_field_callback(); ?></td>
						</tr>
					</tbody></table>
				</div>
			</div>

			<!-- 対象と除外 -->
			<div class="postbox">
				<h2 class="hndle">対象と除外</h2>
				<div class="inside">
					<table class="form-table"><tbody>
						<tr>
							<th scope="row">対象投稿タイプ</th>
							<td><?php ksatl_post_types_field_callback(); ?></td>
						</tr>
						<tr>
							<th scope="row">除外タグ</th>
							<td><?php ksatl_excluded_tags_field_callback(); ?></td>
						</tr>
					</tbody></table>
				</div>
			</div>

			<!-- スタイル設定 -->
			<div class="postbox">
				<h2 class="hndle">スタイル設定</h2>
				<div class="inside">
					<table class="form-table"><tbody>
						<tr>
							<th scope="row">リンクの文字色</th>
							<td><?php ksatl_link_color_mode_field_callback(); ?></td>
						</tr>
						<tr>
							<th scope="row">下線のスタイル</th>
							<td><?php ksatl_link_underline_style_field_callback(); ?></td>
						</tr>
					</tbody></table>
				</div>
			</div>

			<?php submit_button( '変更を保存' ); ?>
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
	<p class="description">ここに入力したタグは、本文中に出現してもリンクに変換されません。カンマ区切りで複数指定できます。例: WordPress, CSS, HTML</p>
	<?php
}

/** 自記事タグの除外フィールド */
function ksatl_exclude_self_tags_field_callback() {
	$options = get_option( KSATL_OPTION_NAME, ksatl_get_default_options() );
	$exclude = isset( $options['exclude_self_tags'] ) ? (bool) $options['exclude_self_tags'] : true;
	?>
	<label for="ksatl_exclude_self_tags">
		<input type="checkbox" id="ksatl_exclude_self_tags" name="<?php echo esc_attr( KSATL_OPTION_NAME ); ?>[exclude_self_tags]" value="1" <?php checked( $exclude, true ); ?>>
		記事自身に付与されたタグへのリンクを除外する
	</label>
	<p class="description">有効にすると、その記事に付与済みのタグはリンクに変換されません。無効にすると、自記事のタグもリンク対象になります。</p>
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
	<div class="ksatl-post-types-grid">
		<?php foreach ( $post_types as $post_type ) :
			$checked = in_array( $post_type->name, $selected_types, true );
		?>
		<label title="<?php echo esc_attr( $post_type->name ); ?>">
			<input type="checkbox" class="ksatl-post-type-cb" name="<?php echo esc_attr( KSATL_OPTION_NAME ); ?>[post_types][]" value="<?php echo esc_attr( $post_type->name ); ?>" <?php checked( $checked, true ); ?>>
			<?php echo esc_html( $post_type->labels->name ); ?>
		</label>
		<?php endforeach; ?>
	</div>
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

/** タグキャッシュ保持時間フィールド */
function ksatl_cache_duration_field_callback() {
	$options  = get_option( KSATL_OPTION_NAME, ksatl_get_default_options() );
	$duration = isset( $options['cache_duration'] ) ? absint( $options['cache_duration'] ) : 86400;

	$choices = array(
		0      => 'なし（キャッシュ無効）',
		3600   => '1時間',
		21600  => '6時間',
		43200  => '12時間',
		86400  => '24時間（デフォルト）',
		172800 => '48時間',
	);
	?>
	<select id="ksatl_cache_duration" name="<?php echo esc_attr( KSATL_OPTION_NAME ); ?>[cache_duration]">
		<?php foreach ( $choices as $value => $label ) : ?>
			<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $duration, $value ); ?>><?php echo esc_html( $label ); ?></option>
		<?php endforeach; ?>
	</select>
	<p class="description">タグ一覧とURLをキャッシュする時間です。タグの追加・編集・削除時にはキャッシュは自動的にクリアされます。</p>
	<?php
}

/** リンクの文字色モードフィールド */
function ksatl_link_color_mode_field_callback() {
	$options    = get_option( KSATL_OPTION_NAME, ksatl_get_default_options() );
	$color_mode = isset( $options['link_color_mode'] ) ? $options['link_color_mode'] : 'inherit';
	$custom_color = isset( $options['link_color_custom'] ) ? $options['link_color_custom'] : '#333333';
	$option_name = esc_attr( KSATL_OPTION_NAME );
	?>
	<fieldset>
		<label style="display:block;margin-bottom:8px;">
			<input type="radio" name="<?php echo $option_name; ?>[link_color_mode]" value="inherit" <?php checked( $color_mode, 'inherit' ); ?> class="ksatl-color-mode-radio">
			テキスト色を継承（周囲の文字と同じ色）
		</label>
		<label style="display:block;margin-bottom:8px;">
			<input type="radio" name="<?php echo $option_name; ?>[link_color_mode]" value="custom" <?php checked( $color_mode, 'custom' ); ?> class="ksatl-color-mode-radio">
			カスタムカラー
		</label>
	</fieldset>
	<div id="ksatl-custom-color-wrap" style="margin-top:8px;<?php echo 'custom' !== $color_mode ? 'display:none;' : ''; ?>">
		<input type="text" id="ksatl_link_color_custom" name="<?php echo $option_name; ?>[link_color_custom]" value="<?php echo esc_attr( $custom_color ); ?>" class="ksatl-color-picker">
	</div>
	<script>
	jQuery(document).ready(function($){
		$('.ksatl-color-picker').wpColorPicker();
		$('.ksatl-color-mode-radio').on('change',function(){
			$('#ksatl-custom-color-wrap').toggle($(this).val()==='custom');
		});
	});
	</script>
	<?php
}

/** 下線のスタイルフィールド */
function ksatl_link_underline_style_field_callback() {
	$options = get_option( KSATL_OPTION_NAME, ksatl_get_default_options() );
	$style   = isset( $options['link_underline_style'] ) ? $options['link_underline_style'] : 'dashed';
	$option_name = esc_attr( KSATL_OPTION_NAME );

	$styles = array(
		'dashed' => '破線（デフォルト）',
		'solid'  => '実線',
		'dotted' => '点線',
		'double' => '二重線',
		'wavy'   => '波線',
		'none'   => 'なし',
	);
	?>
	<select id="ksatl_link_underline_style" name="<?php echo $option_name; ?>[link_underline_style]">
		<?php foreach ( $styles as $value => $label ) : ?>
			<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $style, $value ); ?>><?php echo esc_html( $label ); ?></option>
		<?php endforeach; ?>
	</select>
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

	// 自記事タグの除外
	$new_input['exclude_self_tags'] = ! empty( $input['exclude_self_tags'] );

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

	// リンクの文字色モード
	$new_input['link_color_mode'] = ( isset( $input['link_color_mode'] ) && 'custom' === $input['link_color_mode'] )
		? 'custom'
		: 'inherit';

	// カスタムカラー
	$new_input['link_color_custom'] = isset( $input['link_color_custom'] )
		? sanitize_hex_color( $input['link_color_custom'] )
		: $defaults['link_color_custom'];
	if ( ! $new_input['link_color_custom'] ) {
		$new_input['link_color_custom'] = $defaults['link_color_custom'];
	}

	// キャッシュ保持時間
	$allowed_durations = array( 0, 3600, 21600, 43200, 86400, 172800 );
	$new_input['cache_duration'] = ( isset( $input['cache_duration'] ) && in_array( (int) $input['cache_duration'], $allowed_durations, true ) )
		? (int) $input['cache_duration']
		: $defaults['cache_duration'];

	// 下線のスタイル
	$allowed_styles = array( 'solid', 'dashed', 'dotted', 'double', 'wavy', 'none' );
	$new_input['link_underline_style'] = ( isset( $input['link_underline_style'] ) && in_array( $input['link_underline_style'], $allowed_styles, true ) )
		? $input['link_underline_style']
		: $defaults['link_underline_style'];

	return $new_input;
}
