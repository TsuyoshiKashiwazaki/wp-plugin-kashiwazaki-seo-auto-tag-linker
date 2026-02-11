# 更新履歴

このプロジェクトのすべての変更はこのファイルに記録されます。
フォーマットは [Keep a Changelog](https://keepachangelog.com/ja/1.0.0/) に基づいています。

## [1.0.2] - 2026-02-11

### Added
- 自記事タグ除外オプション（記事自身に付与されたタグへのリンクを除外するか設定可能に）
- タグ名の部分一致防止処理（長いタグ処理後に未リンク出現箇所をプレースホルダーで保護）

### Changed
- 設定画面をpostboxカードレイアウトに刷新（基本設定・リンク動作・対象と除外・スタイル設定の4セクション）
- 投稿タイプ選択UIを3列グリッド・スクロール表示に変更
- 投稿タイプ名のみ表示、スラッグはtitle属性に退避（行を短縮）

## [1.0.1] - 2026-02-10

### Added
- リンク文字色の設定機能（テキスト色を継承 / カスタムカラーから選択可能）
- 下線スタイルの設定機能（破線・実線・点線・二重線・波線・なしから選択可能）
- 設定画面にスタイル設定セクションを追加
- カラーピッカーによるカスタムカラー指定

### Changed
- 除外タグフィールドの説明文をわかりやすく改善

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

[1.0.2]: https://github.com/TsuyoshiKashiwazaki/wp-plugin-kashiwazaki-seo-auto-tag-linker/releases/tag/v1.0.2-dev
[1.0.1]: https://github.com/TsuyoshiKashiwazaki/wp-plugin-kashiwazaki-seo-auto-tag-linker/releases/tag/v1.0.1
[1.0.0]: https://github.com/TsuyoshiKashiwazaki/wp-plugin-kashiwazaki-seo-auto-tag-linker/releases/tag/v1.0.0
