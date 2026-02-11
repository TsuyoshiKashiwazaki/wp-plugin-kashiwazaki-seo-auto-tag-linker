<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * the_content フィルターで自動タグリンクを挿入する
 */
function ksatl_auto_link_tags( $content ) {
	// ガード条件
	if ( is_admin() || is_feed() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return $content;
	}

	$options = get_option( KSATL_OPTION_NAME, ksatl_get_default_options() );

	if ( empty( $options['enabled'] ) ) {
		return $content;
	}

	$current_post_type = get_post_type();
	$allowed_types     = isset( $options['post_types'] ) ? (array) $options['post_types'] : array( 'post' );
	if ( ! in_array( $current_post_type, $allowed_types, true ) ) {
		return $content;
	}

	if ( empty( $content ) ) {
		return $content;
	}

	// 対象タグ一覧を取得
	$tags = ksatl_get_eligible_tags( $options );
	if ( empty( $tags ) ) {
		return $content;
	}

	$max_per_tag = isset( $options['max_links_per_tag'] ) ? absint( $options['max_links_per_tag'] ) : 1;
	$link_target = isset( $options['link_target'] ) ? $options['link_target'] : '_self';

	// <script>...</script> と <style>...</style> を一時退避（内部にHTML風文字列を含む可能性があるため）
	$placeholders = array();
	$content = preg_replace_callback(
		'/<(script|style)([\s>])(.*?)<\/\1\s*>/si',
		function ( $match ) use ( &$placeholders ) {
			$key = '<!--KSATL_PH_' . count( $placeholders ) . '-->';
			$placeholders[ $key ] = $match[0];
			return $key;
		},
		$content
	);

	$tag_placeholders = array();

	// 現在記事のタグ名をプレースホルダーで保護（自記事タグが除外される場合、テキストとして残り部分一致の原因になる）
	$exclude_self = isset( $options['exclude_self_tags'] ) ? (bool) $options['exclude_self_tags'] : true;
	$current_tags = $exclude_self ? get_the_tags() : false;
	if ( ! empty( $current_tags ) && ! is_wp_error( $current_tags ) ) {
		$current_tag_names = wp_list_pluck( $current_tags, 'name' );
		usort( $current_tag_names, function ( $a, $b ) {
			return mb_strlen( $b ) - mb_strlen( $a );
		} );
		foreach ( $current_tag_names as $ctag_name ) {
			if ( mb_strpos( $content, $ctag_name ) !== false ) {
				$key = '<!--KSATL_TG' . count( $tag_placeholders ) . '-->';
				$tag_placeholders[ $key ] = $ctag_name;
				$content = ksatl_protect_tag_text( $content, $ctag_name, $key );
			}
		}
	}

	foreach ( $tags as $tag ) {
		// コンテンツにタグ名が存在しなければスキップ（preg_split・DB問い合わせを回避）
		if ( mb_strpos( $content, $tag->name ) === false ) {
			continue;
		}

		$tag_url = get_term_link( $tag );
		if ( is_wp_error( $tag_url ) ) {
			continue;
		}

		$replaced = ksatl_replace_occurrences( $content, $tag->name, $tag_url, $max_per_tag, $link_target );
		if ( $replaced !== false ) {
			$content = $replaced;
		}

		// 未リンクのタグ名をプレースホルダーで保護（短いタグの部分一致を防止）
		if ( mb_strpos( $content, $tag->name ) !== false ) {
			$key = '<!--KSATL_TG' . count( $tag_placeholders ) . '-->';
			$tag_placeholders[ $key ] = $tag->name;
			$content = ksatl_protect_tag_text( $content, $tag->name, $key );
		}
	}

	// タグ名プレースホルダーを復元（短いタグの部分一致防止用）
	if ( ! empty( $tag_placeholders ) ) {
		$content = str_replace( array_keys( $tag_placeholders ), array_values( $tag_placeholders ), $content );
	}

	// プレースホルダーを復元
	if ( ! empty( $placeholders ) ) {
		$content = str_replace( array_keys( $placeholders ), array_values( $placeholders ), $content );
	}

	return $content;
}

/**
 * 自動リンク対象のタグ一覧を取得する
 */
function ksatl_get_eligible_tags( $options ) {
	// 全タグを取得
	$all_tags = get_tags( array( 'hide_empty' => true ) );
	if ( empty( $all_tags ) || is_wp_error( $all_tags ) ) {
		return array();
	}

	// 現在記事のタグIDを取得（設定で除外が有効な場合のみ）
	$exclude_self = isset( $options['exclude_self_tags'] ) ? (bool) $options['exclude_self_tags'] : true;
	$current_tag_ids = array();
	if ( $exclude_self ) {
		$current_tags = get_the_tags();
		if ( ! empty( $current_tags ) && ! is_wp_error( $current_tags ) ) {
			$current_tag_ids = wp_list_pluck( $current_tags, 'term_id' );
		}
	}

	// 除外タグリストを準備
	$excluded_list = array();
	if ( ! empty( $options['excluded_tags'] ) ) {
		$excluded_list = array_map( 'trim', explode( ',', $options['excluded_tags'] ) );
		$excluded_list = array_map( 'mb_strtolower', $excluded_list );
		$excluded_list = array_filter( $excluded_list );
	}

	$min_length = isset( $options['min_tag_length'] ) ? absint( $options['min_tag_length'] ) : 3;

	// フィルタリング
	$eligible_tags = array();
	foreach ( $all_tags as $tag ) {
		// 現在記事のタグは除外
		if ( in_array( $tag->term_id, $current_tag_ids, true ) ) {
			continue;
		}

		// 最小文字数チェック
		if ( mb_strlen( $tag->name ) < $min_length ) {
			continue;
		}

		// 除外タグリストチェック
		if ( in_array( mb_strtolower( $tag->name ), $excluded_list, true ) ) {
			continue;
		}

		$eligible_tags[] = $tag;
	}

	// 名前の長さ降順でソート（長いタグ優先 → 部分一致防止）
	usort( $eligible_tags, function ( $a, $b ) {
		return mb_strlen( $b->name ) - mb_strlen( $a->name );
	} );

	return $eligible_tags;
}

/**
 * テキストノード内のタグ名をプレースホルダーに置換する（リンク内は除外）
 *
 * 長いタグを処理した後、残りの未リンク出現箇所をプレースホルダーで保護し、
 * 短いタグによる部分一致を防止する。
 */
function ksatl_protect_tag_text( $content, $tag_name, $placeholder ) {
	$parts = preg_split( '/(<[^>]+>)/s', $content, -1, PREG_SPLIT_DELIM_CAPTURE );
	$inside_a = 0;

	foreach ( $parts as $i => $part ) {
		if ( isset( $part[0] ) && $part[0] === '<' ) {
			if ( preg_match( '/^<a[\s>]/i', $part ) ) {
				$inside_a++;
			} elseif ( stripos( $part, '</a' ) === 0 ) {
				$inside_a = max( 0, $inside_a - 1 );
			}
			continue;
		}

		if ( $inside_a > 0 ) {
			continue;
		}

		if ( mb_strpos( $part, $tag_name ) !== false ) {
			$parts[ $i ] = str_replace( $tag_name, $placeholder, $part );
		}
	}

	return implode( '', $parts );
}

/**
 * コンテンツ内のタグ名を最大N回までリンクに置換する
 *
 * HTMLタグを分割し、テキスト部分のみで検索・置換を行う。
 * <a>タグ内、<h1>-<h6>タグ内、および特殊タグ内は置換しない。
 * ※ <script>/<style> は呼び出し元で事前に退避済み。
 *
 * @param string $content  コンテンツHTML
 * @param string $tag_name タグ名
 * @param string $tag_url  タグアーカイブURL
 * @param int    $max      最大置換回数
 * @param string $link_target リンクターゲット
 * @return string|false 置換後のコンテンツ、または未発見の場合 false
 */
function ksatl_replace_occurrences( $content, $tag_name, $tag_url, $max = 1, $link_target = '_self' ) {
	// HTMLタグとテキストに分割
	$parts = preg_split( '/(<[^>]+>)/s', $content, -1, PREG_SPLIT_DELIM_CAPTURE );

	$inside_a       = 0;
	$inside_heading = 0;
	$inside_skip    = 0;
	$replace_count  = 0;

	// 置換を行わない特殊タグ一覧（script/style は呼び出し元で退避済み）
	$skip_tags = array( 'code', 'pre', 'textarea', 'select', 'button', 'svg', 'iframe', 'noscript', 'template', 'canvas', 'video', 'audio', 'object' );

	$target_attr = ( $link_target === '_blank' )
		? ' target="_blank" rel="noopener noreferrer"'
		: '';
	$link = '<a href="' . esc_url( $tag_url ) . '" class="ksatl-auto-link"' . $target_attr . '>' . esc_html( $tag_name ) . '</a>';

	foreach ( $parts as $i => $part ) {
		if ( $replace_count >= $max ) {
			break;
		}

		// HTMLタグ部分の場合、状態を追跡
		if ( isset( $part[0] ) && $part[0] === '<' ) {
			// <a> タグの追跡
			if ( preg_match( '/^<a[\s>]/i', $part ) ) {
				$inside_a++;
			} elseif ( strtolower( substr( $part, 0, 4 ) ) === '</a>' ) {
				$inside_a = max( 0, $inside_a - 1 );
			}

			// <h1>-<h6> タグの追跡
			if ( preg_match( '/^<h[1-6][\s>]/i', $part ) ) {
				$inside_heading++;
			} elseif ( preg_match( '/^<\/h[1-6]>/i', $part ) ) {
				$inside_heading = max( 0, $inside_heading - 1 );
			}

			// 特殊タグの追跡（自己閉じタグ /> は無視）
			if ( substr( $part, -2 ) !== '/>' ) {
				foreach ( $skip_tags as $skip_tag ) {
					if ( preg_match( '/^<' . $skip_tag . '[\s>]/i', $part ) ) {
						$inside_skip++;
						break;
					} elseif ( strncasecmp( $part, '</' . $skip_tag, strlen( $skip_tag ) + 2 ) === 0 ) {
						$inside_skip = max( 0, $inside_skip - 1 );
						break;
					}
				}
			}

			continue;
		}

		// <a>タグ内、見出しタグ内、または特殊タグ内はスキップ
		if ( $inside_a > 0 || $inside_heading > 0 || $inside_skip > 0 ) {
			continue;
		}

		// テキスト部分でタグ名を検索（同一テキストノード内で複数回置換する可能性あり）
		$new_part = '';
		$remaining = $part;

		while ( $replace_count < $max ) {
			$pos = mb_strpos( $remaining, $tag_name );
			if ( $pos === false ) {
				break;
			}

			$new_part .= mb_substr( $remaining, 0, $pos ) . $link;
			$remaining = mb_substr( $remaining, $pos + mb_strlen( $tag_name ) );
			$replace_count++;
		}

		if ( $new_part !== '' ) {
			$parts[ $i ] = $new_part . $remaining;
		}
	}

	if ( $replace_count > 0 ) {
		return implode( '', $parts );
	}

	return false;
}
