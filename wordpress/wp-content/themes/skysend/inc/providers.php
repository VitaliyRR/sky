<?php
/**
 * Selected providers from the original SkySend catalogue, verified 2026-09-16.
 * Catalogue: https://skysend.ru/about/providers.html
 * Original data endpoint: https://skysend.ru/providers.php
 * Images are unchanged PNG responses from the source URL beside each entry.
 * The small original categories are grouped for presentation; this is not a full
 * provider list and does not assert that a particular payment service is active.
 */

if (!defined('ABSPATH')) {
    exit;
}

function skysend_provider_categories(): array
{
    return [
        'communications' => [
            'title' => 'Связь и интернет',
            'items' => [
                // Source: http://cluster.skysend.ru:6721/image.php?provider=359
                ['name' => 'МТС', 'image' => 'mts.png'],
                // Source: http://cluster.skysend.ru:6721/image.php?provider=1134
                ['name' => 'Win Mobile', 'image' => 'win-mobile.png'],
                // Source: http://cluster.skysend.ru:6721/image.php?provider=8450
                ['name' => 'КМВТелеком', 'image' => 'kmvtelecom.png'],
                // Source: http://cluster.skysend.ru:6721/image.php?provider=9253
                ['name' => 'SIPNET', 'image' => 'sipnet.png'],
                // Source: http://cluster.skysend.ru:6721/image.php?provider=4074
                ['name' => 'Дом.ru', 'image' => 'domru.png'],
                // Source: http://cluster.skysend.ru:6721/image.php?provider=6871
                ['name' => 'Зелёная точка', 'image' => 'zelenaya-tochka.png'],
                // Source: http://cluster.skysend.ru:6721/image.php?provider=914
                ['name' => 'Эрлайн', 'image' => 'rline.png'],
                // Source: http://cluster.skysend.ru:6721/image.php?provider=2250
                ['name' => 'Ридлан', 'image' => 'reedlan.png'],
            ],
        ],
        'television' => [
            'title' => 'Телевидение',
            'items' => [
                // Source: http://cluster.skysend.ru:6721/image.php?provider=7
                ['name' => 'НТВ-Плюс', 'image' => 'ntv-plus.png'],
                // Source: http://cluster.skysend.ru:6721/image.php?provider=6581
                ['name' => 'Билайн ТВ', 'image' => 'beeline-tv.png'],
                // Source: http://cluster.skysend.ru:6721/image.php?provider=258
                ['name' => 'Триколор', 'image' => 'tricolor.png'],
                // Source: http://cluster.skysend.ru:6721/image.php?provider=4323
                ['name' => 'Город-ТВ', 'image' => 'gorod-tv.png'],
                // Source: http://cluster.skysend.ru:6721/image.php?provider=2249
                ['name' => 'Ридлан ТВ', 'image' => 'reedlan-tv.png'],
                // Source: http://cluster.skysend.ru:6721/image.php?provider=7331
                ['name' => 'ТЦ Электрон', 'image' => 'elektron-tv.png'],
            ],
        ],
        'finance' => [
            'title' => 'Банки и кошельки',
            'items' => [
                // Source: http://cluster.skysend.ru:6721/image.php?provider=631
                ['name' => 'Сбербанк', 'image' => 'sberbank.png'],
                // Source: http://cluster.skysend.ru:6721/image.php?provider=241
                ['name' => 'Т-Банк', 'image' => 't-bank.png'],
                // Source: http://cluster.skysend.ru:6721/image.php?provider=168
                ['name' => 'Альфа-Банк', 'image' => 'alfa-bank.png'],
                // Source: http://cluster.skysend.ru:6721/image.php?provider=191
                ['name' => 'ВТБ Северо-Запад', 'image' => 'vtb.png'],
                // Source: http://cluster.skysend.ru:6721/image.php?provider=232
                ['name' => 'Россельхозбанк', 'image' => 'rosselkhozbank.png'],
                // Source: http://cluster.skysend.ru:6721/image.php?provider=226
                ['name' => 'ОТП Банк', 'image' => 'otp-bank.png'],
                // Source: http://cluster.skysend.ru:6721/image.php?provider=1098
                ['name' => 'Совкомбанк', 'image' => 'sovcombank.png'],
                // Source: http://cluster.skysend.ru:6721/image.php?provider=144
                ['name' => 'Монета.ру', 'image' => 'moneta.png'],
            ],
        ],
        'digital' => [
            'title' => 'Игры и соцсети',
            'items' => [
                // Source: http://cluster.skysend.ru:6721/image.php?provider=4819
                ['name' => 'ВКонтакте', 'image' => 'vk.png'],
                // Source: http://cluster.skysend.ru:6721/image.php?provider=4816
                ['name' => 'Одноклассники', 'image' => 'ok.png'],
                // Source: http://cluster.skysend.ru:6721/image.php?provider=4820
                ['name' => 'Мой Мир', 'image' => 'my-world.png'],
                // Source: http://cluster.skysend.ru:6721/image.php?provider=1074
                ['name' => 'Галактика знакомств', 'image' => 'galaxy.png'],
                // Source: http://cluster.skysend.ru:6721/image.php?provider=4675
                ['name' => 'Warface', 'image' => 'warface.png'],
                // Source: http://cluster.skysend.ru:6721/image.php?provider=4755
                ['name' => 'ArcheAge', 'image' => 'archeage.png'],
                // Source: http://cluster.skysend.ru:6721/image.php?provider=4729
                ['name' => 'Perfect World', 'image' => 'perfect-world.png'],
                // Source: http://cluster.skysend.ru:6721/image.php?provider=252
                ['name' => 'Xsolla', 'image' => 'xsolla.png'],
            ],
        ],
        'utilities' => [
            'title' => 'ЖКХ',
            'items' => [
                // Source: http://cluster.skysend.ru:6721/image.php?provider=9416
                ['name' => 'Волгоградэнергосбыт', 'image' => 'volgogradenergosbyt.png'],
                // Source: http://cluster.skysend.ru:6721/image.php?provider=253
                ['name' => 'ТНС энерго Кубань', 'image' => 'tns-energo.png'],
                // Source: http://cluster.skysend.ru:6721/image.php?provider=8271
                ['name' => 'Крымская Водная Компания', 'image' => 'crimea-water.png'],
                // Source: http://cluster.skysend.ru:6721/image.php?provider=8640
                ['name' => 'Оплата ЖКУ (ГИС ЖКХ)', 'image' => 'gis-zhkh.png'],
                // Source: http://cluster.skysend.ru:6721/image.php?provider=1152
                ['name' => 'ГарантСтрой-Юг', 'image' => 'garantstroy-yug.png'],
                // Source: http://cluster.skysend.ru:6721/image.php?provider=1148
                ['name' => 'Градострой', 'image' => 'gradostroy.png'],
                // Source: http://cluster.skysend.ru:6721/image.php?provider=1160
                ['name' => 'УК Управдом', 'image' => 'upravdom.png'],
                // Source: http://cluster.skysend.ru:6721/image.php?provider=1164
                ['name' => 'УК Екатеринодар', 'image' => 'ekaterinodar.png'],
            ],
        ],
        'services' => [
            'title' => 'Сервисы и прочие услуги',
            'items' => [
                // Source: http://cluster.skysend.ru:6721/image.php?provider=630
                ['name' => 'AVON', 'image' => 'avon.png'],
                // Source: http://cluster.skysend.ru:6721/image.php?provider=644
                ['name' => 'Faberlic', 'image' => 'faberlic.png'],
                // Source: http://cluster.skysend.ru:6721/image.php?provider=2268
                ['name' => 'ТБГ', 'image' => 'tbg.png'],
                // Source: http://cluster.skysend.ru:6721/image.php?provider=9151
                ['name' => 'ВСК Страхование', 'image' => 'vsk.png'],
                // Source: http://cluster.skysend.ru:6721/image.php?provider=8459
                ['name' => 'МСК АйАйСи', 'image' => 'iic.png'],
                // Source: http://cluster.skysend.ru:6721/image.php?provider=8526
                ['name' => 'ЦСМТ ЗДОРОВЬЕ', 'image' => 'health.png'],
                // Source: http://cluster.skysend.ru:6721/image.php?provider=323
                ['name' => 'Еммануил', 'image' => 'emmanuel.png'],
                // Source: http://cluster.skysend.ru:6721/image.php?provider=8455
                ['name' => 'Такси Союз', 'image' => 'taxi-soyuz.png'],
            ],
        ],
    ];
}
