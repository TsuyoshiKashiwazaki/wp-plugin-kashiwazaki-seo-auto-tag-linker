<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * the_content フィルターで自動タグリンクを挿入する（シングルパス最適化版）
 *
 * コンテンツを1回だけ preg_split() し、全タグをその配列上で処理する。
 * タグマッチごとの分割・再結合を排除し、大幅にパフォーマンスを向上。
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

	// キャッシュ済みタグ一覧を取得（自記事タグ除外前のグローバルリスト）
	$all_eligible_tags = ksatl_get_eligible_tags_cached( $options );
	if ( empty( $all_eligible_tags ) ) {
		return $content;
	}

	$max_per_tag  = isset( $options['max_links_per_tag'] ) ? absint( $options['max_links_per_tag'] ) : 1;
	$link_target  = isset( $options['link_target'] ) ? $options['link_target'] : '_self';
	$exclude_self = isset( $options['exclude_self_tags'] ) ? (bool) $options['exclude_self_tags'] : true;

	// 自記事タグを取得・除外
	$current_tag_ids   = array();
	$current_tag_names = array();
	if ( $exclude_self ) {
		$current_tags = get_the_tags();
		if ( ! empty( $current_tags ) && ! is_wp_error( $current_tags ) ) {
			$current_tag_ids   = wp_list_pluck( $current_tags, 'term_id' );
			$current_tag_names = wp_list_pluck( $current_tags, 'name' );
			// 長い順にソート（部分一致防止）
			usort( $current_tag_names, function ( $a, $b ) {
				return mb_strlen( $b ) - mb_strlen( $a );
			} );
		}
	}

	// キャッシュリストから自記事タグを除外
	if ( ! empty( $current_tag_ids ) ) {
		$eligible_tags = array();
		foreach ( $all_eligible_tags as $tag ) {
			if ( ! in_array( $tag->term_id, $current_tag_ids, true ) ) {
				$eligible_tags[] = $tag;
			}
		}
	} else {
		$eligible_tags = $all_eligible_tags;
	}

	if ( empty( $eligible_tags ) ) {
		return $content;
	}

	// Phase 1: <script>/<style> を一時退避
	$script_placeholders = array();
	$content = preg_replace_callback(
		'/<(script|style)([\s>])(.*?)<\/\1\s*>/si',
		function ( $match ) use ( &$script_placeholders ) {
			$key = '<!--KSATL_PH_' . count( $script_placeholders ) . '-->';
			$script_placeholders[ $key ] = $match[0];
			return $key;
		},
		$content
	);

	// Phase 2: コンテンツを1回だけ分割
	$parts = preg_split( '/(<[^>]+>)/s', $content, -1, PREG_SPLIT_DELIM_CAPTURE );

	// プレースホルダー管理
	$ph_counter        = 0;
	$link_placeholders = array();
	$tag_placeholders  = array();

	// 自記事タグ用プレースホルダーを事前作成（長い順）
	$self_tag_phs = array();
	foreach ( $current_tag_names as $ctag_name ) {
		$key                          = '<!--KSATL_TG' . $ph_counter++ . '-->';
		$self_tag_phs[ $ctag_name ]   = $key;
		$tag_placeholders[ $key ]     = $ctag_name;
	}

	// 対象タグ用保護プレースホルダーを事前作成（長い順、eligible_tags はソート済み）
	$eligible_tag_phs = array();
	foreach ( $eligible_tags as $tag ) {
		$key                              = '<!--KSATL_TG' . $ph_counter++ . '-->';
		$eligible_tag_phs[ $tag->name ]   = $key;
		$tag_placeholders[ $key ]         = $tag->name;
	}

	// リンクHTML生成用の属性
	$target_attr = ( $link_target === '_blank' )
		? ' target="_blank" rel="noopener noreferrer"'
		: '';

	// グローバル置換カウンター（テキストノードを跨いでカウント）
	$replace_counts = array();

	// コンテキスト追跡
	$inside_a       = 0;
	$inside_heading = 0;
	$inside_skip    = 0;
	$skip_tags      = array( 'code', 'pre', 'textarea', 'select', 'button', 'svg', 'iframe', 'noscript', 'template', 'canvas', 'video', 'audio', 'object' );

	// Phase 3: パーツをイテレートし、テキストノードで全タグを一括処理
	foreach ( $parts as $i => $part ) {
		// HTMLタグ部分の場合、コンテキストを追跡
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

		// 保護コンテキスト内はスキップ
		if ( $inside_a > 0 || $inside_heading > 0 || $inside_skip > 0 ) {
			continue;
		}

		$text = $part;

		// 自記事タグ名をプレースホルダーで保護（長い順）
		foreach ( $self_tag_phs as $name => $ph ) {
			if ( mb_strpos( $text, $name ) !== false ) {
				$text = str_replace( $name, $ph, $text );
			}
		}

		// 対象タグを処理（長い順、eligible_tags はソート済み）
		foreach ( $eligible_tags as $tag ) {
			if ( mb_strpos( $text, $tag->name ) === false ) {
				continue;
			}

			if ( ! isset( $replace_counts[ $tag->name ] ) ) {
				$replace_counts[ $tag->name ] = 0;
			}

			$remaining = $max_per_tag - $replace_counts[ $tag->name ];

			// リンク置換（max_per_tag 未達の場合）
			if ( $remaining > 0 ) {
				$link_html = '<a href="' . esc_url( $tag->url ) . '" class="ksatl-auto-link"' . $target_attr . '>' . esc_html( $tag->name ) . '</a>';
				$count     = 0;
				$result    = '';
				$search    = $text;

				while ( $count < $remaining ) {
					$pos = mb_strpos( $search, $tag->name );
					if ( $pos === false ) {
						break;
					}

					$lk_key                       = '<!--KSATL_LK' . count( $link_placeholders ) . '-->';
					$link_placeholders[ $lk_key ] = $link_html;
					$result .= mb_substr( $search, 0, $pos ) . $lk_key;
					$search  = mb_substr( $search, $pos + mb_strlen( $tag->name ) );
					$count++;
				}

				$text = $result . $search;
				$replace_counts[ $tag->name ] += $count;
			}

			// 残りをプレースホルダーで保護（短いタグの部分一致防止）
			if ( mb_strpos( $text, $tag->name ) !== false ) {
				$text = str_replace( $tag->name, $eligible_tag_phs[ $tag->name ], $text );
			}
		}

		$parts[ $i ] = $text;
	}

	// Phase 4: 再結合
	$content = implode( '', $parts );

	// Phase 5: プレースホルダー復元（リンク → タグ名 → script/style の順）
	if ( ! empty( $link_placeholders ) ) {
		$content = str_replace( array_keys( $link_placeholders ), array_values( $link_placeholders ), $content );
	}

	if ( ! empty( $tag_placeholders ) ) {
		$content = str_replace( array_keys( $tag_placeholders ), array_values( $tag_placeholders ), $content );
	}

	if ( ! empty( $script_placeholders ) ) {
		$content = str_replace( array_keys( $script_placeholders ), array_values( $script_placeholders ), $content );
	}

	return $content;
}

/**
 * 自動リンク対象のタグ一覧をキャッシュ付きで取得する
 *
 * static変数（同一リクエスト内キャッシュ）+ WordPress Transient（クロスリクエストキャッシュ）。
 * URL事前解決済み。自記事タグの除外は呼び出し元で行う。
 */
function ksatl_get_eligible_tags_cached( $options ) {
	static $cached = null;
	if ( $cached !== null ) {
		return $cached;
	}

	$cache_duration = isset( $options['cache_duration'] ) ? absint( $options['cache_duration'] ) : 86400;
	$transient_key  = 'ksatl_tags_cache';

	if ( $cache_duration > 0 ) {
		$cached = get_transient( $transient_key );
		if ( $cached !== false ) {
			return $cached;
		}
	}

	$all_tags = get_tags( array( 'hide_empty' => true ) );
	if ( empty( $all_tags ) || is_wp_error( $all_tags ) ) {
		$cached = array();
		if ( $cache_duration > 0 ) {
			set_transient( $transient_key, $cached, $cache_duration );
		}
		return $cached;
	}

	$min_length    = isset( $options['min_tag_length'] ) ? absint( $options['min_tag_length'] ) : 3;
	$excluded_list = array();
	if ( ! empty( $options['excluded_tags'] ) ) {
		$excluded_list = array_map( 'trim', explode( ',', $options['excluded_tags'] ) );
		$excluded_list = array_map( 'mb_strtolower', $excluded_list );
		$excluded_list = array_filter( $excluded_list );
	}

	$eligible = array();
	foreach ( $all_tags as $tag ) {
		if ( mb_strlen( $tag->name ) < $min_length ) {
			continue;
		}

		if ( in_array( mb_strtolower( $tag->name ), $excluded_list, true ) ) {
			continue;
		}

		$url = get_term_link( $tag );
		if ( is_wp_error( $url ) ) {
			continue;
		}

		$eligible[] = (object) array(
			'term_id' => $tag->term_id,
			'name'    => $tag->name,
			'url'     => $url,
		);
	}

	// 名前の長さ降順でソート（長いタグ優先 → 部分一致防止）
	usort( $eligible, function ( $a, $b ) {
		return mb_strlen( $b->name ) - mb_strlen( $a->name );
	} );

	$cached = $eligible;
	if ( $cache_duration > 0 ) {
		set_transient( $transient_key, $eligible, $cache_duration );
	}
	return $cached;
}
