=== Kashiwazaki SEO Auto Tag Linker ===
Contributors: tsuyoshikashiwazaki
Tags: seo, internal-links, auto-link, tag-links, internal-linking
Requires at least: 5.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

投稿コンテンツ内のタグ名に一致するテキストを自動的にタグアーカイブページへのリンクに変換し、内部リンク構造を強化するSEOプラグインです。

== Description ==

Kashiwazaki SEO Auto Tag Linker は、投稿コンテンツ内のタグ名を検出し、対応するタグアーカイブページへのリンクに自動変換するWordPressプラグインです。

内部リンク構造を強化することで、サイトのSEO効果を高めます。

= 主な機能 =

* **自動タグリンク変換** - 投稿コンテンツ内のタグ名を自動的にリンクに変換
* **同一タグリンク回数制御** - 同じタグの最大リンク変換回数を制限
* **最小タグ文字数フィルタ** - 短いタグ名の誤マッチを防止
* **除外タグリスト** - 特定のタグを自動リンク対象外に設定
* **カスタム投稿タイプ対応** - 投稿以外の投稿タイプにも適用可能
* **リンクターゲット設定** - 同じタブまたは新しいタブで開くかを選択
* **破線アンダーラインスタイル** - 自動リンクを通常リンクと視覚的に区別
* **安全なHTML処理** - `<a>`, `<h1>`〜`<h6>`, `<script>`, `<style>`, `<code>`, `<pre>`, `<textarea>`, `<select>`, `<button>`, `<svg>`, `<iframe>`, `<canvas>`, `<video>`, `<audio>`, `<object>`, `<noscript>`, `<template>` 内のテキストは自動リンク対象外

== Installation ==

1. プラグインフォルダを `/wp-content/plugins/` ディレクトリにアップロードします
2. WordPress管理画面の「プラグイン」メニューからプラグインを有効化します
3. 管理画面左メニューの「Kashiwazaki SEO Auto Tag Linker」から設定を行います

== Frequently Asked Questions ==

= 自動リンクの対象になるタグはどれですか？ =

投稿に直接割り当てられたタグを除く、公開済みの全タグが対象となります。最小文字数や除外リストによるフィルタリングも適用されます。

= 見出し内やコード内のテキストもリンクに変換されますか？ =

いいえ。`<a>`, `<h1>`〜`<h6>`, `<script>`, `<style>`, `<code>`, `<pre>` などの特殊タグ内のテキストは自動リンクの対象外です。

= 自動生成されたリンクを見分けることはできますか？ =

はい。自動生成されたリンクには `ksatl-auto-link` クラスが付与され、控えめな破線アンダーラインで表示されます。

== Screenshots ==

1. 設定画面 - 各種オプションを管理画面から設定できます
2. 自動リンク動作例 - 投稿コンテンツ内のタグ名が自動的にリンクに変換されます

== Changelog ==

= 1.0.0 =
* 初回リリース
* 投稿コンテンツ内のタグ名を自動的にタグアーカイブページへのリンクに変換
* 同一タグリンク回数制御機能
* 最小タグ文字数フィルタ機能
* 除外タグリスト機能
* カスタム投稿タイプ対応
* リンクターゲット設定（同じタブ / 新しいタブ）
* 破線アンダーラインスタイルによる自動リンク識別
* 特殊HTMLタグ内（script, style, code, pre 等）のテキストを自動リンク対象外にする安全処理
