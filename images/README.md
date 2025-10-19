# アイコン画像について

このディレクトリには以下のPWAアイコンが必要です:

- `icon-192.png` (192x192px)
- `icon-512.png` (512x512px)

## アイコンの生成方法

### オプション1: オンラインツールを使用

1. [Favicon Generator](https://realfavicongenerator.net/) にアクセス
2. `icon.svg` をアップロード
3. PWA用のアイコンを生成
4. 生成された `icon-192.png` と `icon-512.png` をこのフォルダに配置

### オプション2: ImageMagickを使用

```bash
# SVGからPNGに変換
convert icon.svg -resize 192x192 icon-192.png
convert icon.svg -resize 512x512 icon-512.png
```

### オプション3: オンライン変換ツール

1. [CloudConvert](https://cloudconvert.com/svg-to-png) にアクセス
2. `icon.svg` をアップロード
3. 192x192と512x512のサイズで変換
4. ダウンロードして配置

## 注意事項

- アイコンは正方形である必要があります
- 背景は透明ではなく、色を指定することを推奨します
- 現在のSVGはプレースホルダーです。実際のアプリに合わせてデザインしてください
