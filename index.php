<?php
// ===============================
// Fake SteamPulse Telegram Bot
// ===============================
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

    if ($uri === '/ping') {
        echo "pong";
        http_response_code(200);
        exit;
    }
}

$BOT_TOKEN = getenv("TELEGRAM_BOT_TOKEN");
$API_URL = "https://api.telegram.org/bot$BOT_TOKEN/";

function sendMessage($chat_id, $text, $reply_markup = null) {
    global $API_URL;
    $params = [
        'chat_id' => $chat_id,
        'text' => $text,
        'parse_mode' => "HTML"
    ];
    if ($reply_markup) $params['reply_markup'] = json_encode($reply_markup);
    @file_get_contents($API_URL . "sendMessage?" . http_build_query($params));
}

function editMessageReplyMarkup($chat_id, $message_id, $reply_markup) {
    global $API_URL;
    if (!$message_id) return;
    $params = [
        'chat_id' => $chat_id,
        'message_id' => $message_id,
        'reply_markup' => json_encode($reply_markup)
    ];
    @file_get_contents($API_URL . "editMessageReplyMarkup?" . http_build_query($params));
}

function getPrice($appid, $currency, $market_hash_name, $divide = 1) {
    $json = @file_get_contents("https://steamcommunity.com/market/priceoverview/?appid=$appid&currency=$currency&market_hash_name=" . urlencode($market_hash_name));
    if (!$json) return null;
    $obj = json_decode($json);
    if (!$obj || !isset($obj->lowest_price)) return null;
    $price = preg_replace("/[^0-9\.]/", '', $obj->lowest_price);
    return $price / $divide;
}

function sendRegionPrices($chat_id, $type, $regions) {
    $emoji = ($type == "key") ? "🔑" : "🎫";
    $msg = "";

    if (count($regions) > 1) {
        $line = [];
        foreach ($regions as $r) $line[] = $r[0] . " " . $r[1];
        $msg .= "<b>$emoji " . implode(", ", $line) . "</b>\n\n";
        $r = $regions[0];
    } else {
        $r = $regions[0];
        $msg .= "<b>$emoji {$r[0]} {$r[1]}</b>\n\n";
    }

    $marketName = ($type == "key") ? "Mann Co. Supply Crate Key" : "Tour of Duty Ticket";
    $price = getPrice(440, $r[2], $marketName, $r[3]);
    if ($price === null) {
        sendMessage($chat_id, "⚠️ Unable to fetch price from Steam. Please try again later.", [
            'inline_keyboard' => [[['text' => "⬅️ Back", 'callback_data' => 'back_price']]]
        ]);
        return;
    }

    $net = $price / 1.15;
    $tax = $price - $net;

    $currencySymbol = match($r[0]) {
        "Ukraine" => "₴",
        "Russia" => "pуб.",
        "India" => "₹",
        "Brazil" => "R$",
        "Kazakhstan" => "₸",
        "China" => "¥",
        default => "$",
    };

    $msg .= "Full Price: " . number_format($price,2,'.','') . " $currencySymbol\n";
    $msg .= "Net Price: " . number_format($net,2,'.','') . " $currencySymbol\n";
    $msg .= "Tax: " . number_format($tax,2,'.','') . " $currencySymbol\n";

    $backButton = [
        'inline_keyboard' => [
            [['text' => "⬅️ Back", 'callback_data' => 'back_price']]
        ]
    ];
    sendMessage($chat_id, trim($msg), $backButton);
}

$content = file_get_contents("php://input");
$update = json_decode($content, true);
if (!$update) exit;

$chat_id = $update["message"]["chat"]["id"] ?? $update["callback_query"]["message"]["chat"]["id"] ?? null;
$message_id = $update["callback_query"]["message"]["message_id"] ?? null;
$text = $update["message"]["text"] ?? null;
$data = $update["callback_query"]["data"] ?? null;
if (!$chat_id) exit;

$mainMenu = [
    'inline_keyboard' => [
        [
            ['text' => "🔑 Key", 'callback_data' => 'menu_key'],
            ['text' => "🎫 Ticket", 'callback_data' => 'menu_ticket']
        ],
        [
            ['text' => "ℹ️ About", 'callback_data' => 'about']
        ]
    ]
];

$regionsMap = [
    'region_row1' => [
        ["USA", "🇺🇸", 1, 1],
        ["Argentina", "🇦🇷", 1, 1],
        ["Turkey", "🇹🇷", 1, 1]
    ],
    'region_ukraine' => [["Ukraine", "🇺🇦", 18, 1]],
    'region_russia' => [["Russia", "🇷🇺", 5, 100]],
    'region_brazil' => [["Brazil", "🇧🇷", 7, 100]],
    'region_india' => [["India", "🇮🇳", 24, 1]],
    'region_kazakhstan' => [["Kazakhstan", "🇰🇿", 37, 1]],
    'region_china' => [["China", "🇨🇳", 23, 1]]
];

if ($text == "/start") {
    $welcome = "👋 <b>Welcome to Fake SteamPulse Bot!</b>\n\n".
               "Check real-time prices for 🔑 <b>Keys</b> and 🎫 <b>Tickets</b> across different Steam regions.\n\n".
               "Choose an option below to begin:";
    sendMessage($chat_id, $welcome, $mainMenu);
}

if ($text == "/about" || $data == "about") {
    $aboutText = "This is a hobby project based on my best friend SteamPulse Web project.\n";
    $aboutText .= "Shoutout to him for his amazing work! @Amirhoseindavat ♡\n\n";
    $aboutText .= "Repo: https://github.com/MehdiAnti/FakeSteamPulseFUN\n";
    $aboutText .= "Original: https://github.com/CodeMageIR/SteamPulse_Web";
    $backButton = [
        'inline_keyboard' => [
            [['text' => "⬅️ Back", 'callback_data' => 'back_about']]
        ]
    ];
    sendMessage($chat_id, $aboutText, $backButton);
}

function sendRegionMenu($chat_id, $type, $title) {
    $buttons = [
        [
            ['text' => "🇺🇸 USA, 🇦🇷 Argentina, 🇹🇷 Turkey", 'callback_data' => 'region_row1_' . $type]
        ],
        [
            ['text' => "🇺🇦 Ukraine", 'callback_data' => 'region_ukraine_' . $type],
            ['text' => "🇷🇺 Russia", 'callback_data' => 'region_russia_' . $type]
        ],
        [
            ['text' => "🇧🇷 Brazil", 'callback_data' => 'region_brazil_' . $type],
            ['text' => "🇮🇳 India", 'callback_data' => 'region_india_' . $type]
        ],
        [
            ['text' => "🇰🇿 Kazakhstan", 'callback_data' => 'region_kazakhstan_' . $type],
            ['text' => "🇨🇳 China", 'callback_data' => 'region_china_' . $type]
        ],
        [
            ['text' => "⬅️ Back", 'callback_data' => 'back']
        ]
    ];
    sendMessage($chat_id, $title, ['inline_keyboard' => $buttons]);
}

if ($text == "/key") sendRegionMenu($chat_id, "key", "Select a region for Keys:");
if ($text == "/ticket") sendRegionMenu($chat_id, "ticket", "Select a region for Tickets:");

if ($data == "menu_key" || $data == "menu_ticket") {
    $type = ($data == "menu_key") ? "key" : "ticket";
    $buttons = [
        [
            ['text' => "🇺🇸 USA, 🇦🇷 Argentina, 🇹🇷 Turkey", 'callback_data' => 'region_row1_' . $type]
        ],
        [
            ['text' => "🇺🇦 Ukraine", 'callback_data' => 'region_ukraine_' . $type],
            ['text' => "🇷🇺 Russia", 'callback_data' => 'region_russia_' . $type]
        ],
        [
            ['text' => "🇧🇷 Brazil", 'callback_data' => 'region_brazil_' . $type],
            ['text' => "🇮🇳 India", 'callback_data' => 'region_india_' . $type]
        ],
        [
            ['text' => "🇰🇿 Kazakhstan", 'callback_data' => 'region_kazakhstan_' . $type],
            ['text' => "🇨🇳 China", 'callback_data' => 'region_china_' . $type]
        ],
        [
            ['text' => "⬅️ Back", 'callback_data' => 'back']
        ]
    ];
    editMessageReplyMarkup($chat_id, $message_id, ['inline_keyboard' => $buttons]);
}

if ($data === 'back_price' || $data === 'back_about') sendMessage($chat_id, "Choose an option:", $mainMenu);
if ($data === 'back') editMessageReplyMarkup($chat_id, $message_id, $mainMenu);

foreach ($regionsMap as $regionKey => $regionArr) {
    foreach (["key","ticket"] as $type) {
        if ($data == $regionKey . "_" . $type) sendRegionPrices($chat_id, $type, $regionArr);
    }
}
