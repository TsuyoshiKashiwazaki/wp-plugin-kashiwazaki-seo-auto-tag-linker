# 更新履歴

このプロジェクトのすべての変更はこのファイルに記録されます。
フォーマットは [Keep a Changelog](https://keepachangelog.com/ja/1.0.0/) に基づいています。

## [1.0.0] - 2026-02-10

### Added
- 投稿コンテンツ内のタグ名を自動的にタグアーカイブページへのリンクに変換する機能
- 同一タグの最大リンク回数制御（1記事内で同じタグをリンクにする最大回数を設定）
- 最小タグ文字数フィルタ（短いタグの誤マッチ防止）
- 除外タグリスト（カンマ区切りで自動リンク対象外のタグを指定）
- カスタム投稿タイプ対応（投稿以外の投稿タイプにも適用可能）
- リンクターゲット設定（同じタブ / 新しいタブ）
- 破線アンダーラインスタイルによる自動リンクの視覚的識別
- 特殊HTMLタグ内のテキストを自動リンク対象外にする安全処理（`<a>`, `<h1>`〜`<h6>`, `<script>`, `<style>`, `<code>`, `<pre>`, `<textarea>`, `<select>`, `<button>`, `<svg>`, `<iframe>`, `<canvas>`, `<video>`, `<audio>`, `<object>`, `<noscript>`, `<template>`）
- 管理画面からの設定ページ

[1.0.0]: https://github.com/TsuyoshiKashiwazaki/wp-plugin-kashiwazaki-seo-auto-tag-linker/releases/tag/v1.0.0-dev
