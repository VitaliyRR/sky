# Плитка «Шлюзовикам»: удалить XML

Надпись `xml` находилась внутри оригинальной фотографии, а не в HTML-плашке.
Встроенный `image_gen` использован в режиме редактирования изображения:
удалены буквы в верхнем левом углу. Оригинал `partner-gateways.jpg` сохранён.
Новая версия используется только на партнёрской плитке главной страницы.
Из описания этой плитки также убрано упоминание XML; техническое описание
протокола в программах и на странице шлюзовиков не изменялось.

Итоговые файлы:

- [PNG](C:/Users/vitaliy/Documents/ChatGPT/skysend/wordpress/wp-content/themes/skysend/assets/images/partner-gateways-no-xml-20260916.png).
- [WebP на сайте](C:/Users/vitaliy/Documents/ChatGPT/skysend/wordpress/wp-content/themes/skysend/assets/images/partner-gateways-no-xml-20260916.webp) — кодирование PNG без потерь, без дополнительной ретуши.

Запрос к встроенному генератору (CLI не использовался):

```text
Use case: precise-object-edit. Image 1: edit target, the original SkySend gateway partner photograph. Remove ONLY the large gray lowercase word "xml" in the upper-left corner. Fill that small letter area with the matching light-gray background seamlessly. Preserve the same woman, identity, face, hair, headset, hand gesture, clothes, framing, translucent circular interface, small binary decorations, colors, light and all other details unchanged. Do not redesign or add anything, no new letters, no new text, no watermark. Preserve the original 453:367 image proportions.
```
