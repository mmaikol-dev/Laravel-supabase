<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class TelegramController extends Controller
{
    // ===== HARD-CODED VALUES =====
    private $botToken     = "7701266590:AAHIN9ZH88CbmoZFRie1s4Y7zUZAd5nc_yA";
    private $botUsername  = "batmantrial_bot";
    private $webhookUrl   = "https://sitebase.co.ke/telegram/webhook";

    // Tenor API Key (for GIFs) -> use any key you have
    private $tenorKey     = "AIzaSyAXpuj14X0QHUcarzStdsKrfZ4Ahy-fIPA";

    // CoinGecko endpoint
    private $coingeckoBase = "https://api.coingecko.com/api/v3";
    // =============================

    public function __construct()
    {
        Log::info("TelegramController loaded with HARD CODED VALUES", [
            'token'     => $this->botToken,
            'username'  => $this->botUsername,
            'webhook'   => $this->webhookUrl,
            'tenor'     => $this->tenorKey
        ]);
    }

    public function webhook(Request $request)
    {
        Log::info("Webhook update received", $request->all());

        // Respond instantly to Telegram to avoid timeout
        response("OK", 200)->send();

        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }

        $update = $request->all();

        if (isset($update["callback_query"])) {
            $this->handleCallbackQuery($update["callback_query"]);
            return;
        }

        if (!isset($update["message"])) {
            return;
        }

        $message = $update["message"];
        $chatId  = $message["chat"]["id"];
        $text    = $message["text"] ?? "";

        if (str_starts_with($text, "/")) {
            $this->handleCommand($chatId, $text, $message);
            return;
        }

        // Stickers reply
        if (isset($message["sticker"])) {
            $this->sendMessage($chatId, "ðŸ”¥ Nice sticker! Use /market for updates.");
            return;
        }

        // Default reply
        $this->sendMessage($chatId, "Hi there ðŸ‘‹ Use /help to see what I can do.");
    }

    private function handleCommand($chatId, $cmd, $message = null)
    {
        $cmd = explode(" ", trim($cmd))[0];

        switch ($cmd) {
            case "/start":
                $this->sendMessage($chatId, "ðŸ¦‡ Welcome to Gotham Trading Bot!");
                $this->sendGifByQuery($chatId, "batman trading");
                $this->sendInlineMenu($chatId);
                break;

            case "/help":
                $this->sendMessage($chatId, "ðŸ“˜ Commands:\n/start\n/help\n/market\n/prices\n/gif <term>");
                break;

            case "/market":
                $this->sendMarketWithGif($chatId);
                break;

            case "/prices":
                $this->sendPrices($chatId);
                break;

            case "/gif":
                $query = trim(str_replace("/gif", "", $message["text"]));
                $this->sendGifByQuery($chatId, $query ?: "trading");
                break;

            default:
                $this->sendMessage($chatId, "Unknown command. Try /help.");
        }
    }

    private function sendMarketWithGif($chatId)
    {
        $prices = $this->getPrices(["bitcoin", "ethereum"]);

        $text = "ðŸ“ˆ *Market Summary*\n"
            . "BTC: $" . number_format($prices["bitcoin"], 2) . "\n"
            . "ETH: $" . number_format($prices["ethereum"], 2);

        $this->sendMessage($chatId, $text);
        $this->sendGifByQuery($chatId, "crypto market");
    }

    private function sendPrices($chatId)
    {
        $prices = $this->getPrices(["bitcoin","ethereum","litecoin"]);

        $reply = "ðŸ’¹ *Prices*\n";
        foreach ($prices as $k => $v) {
            $reply .= strtoupper($k) . ": $" . number_format($v, 2) . "\n";
        }

        $this->sendMessage($chatId, $reply);
    }

    private function getPrices($ids)
    {
        return Cache::remember("cg_" . implode("_",$ids), 30, function() use ($ids) {

            try {
                $res = Http::get($this->coingeckoBase . "/simple/price", [
                    "ids" => implode(",", $ids),
                    "vs_currencies" => "usd"
                ]);

                $json = $res->json();
                $out = [];
                foreach ($ids as $id) {
                    $out[$id] = $json[$id]["usd"] ?? 0;
                }
                return $out;

            } catch (\Throwable $e) {
                Log::error("Coingecko error: " . $e->getMessage());
                return array_fill_keys($ids, 0);
            }

        });
    }

    private function getGifFromTenor($query)
    {
        try {
            $res = Http::get("https://tenor.googleapis.com/v2/search", [
                "key" => $this->tenorKey,
                "q" => $query,
                "limit" => 1,
                "media_filter" => "minimal"
            ]);

            $json = $res->json();

            if (isset($json["results"][0]["media_formats"]["gif"]["url"])) {
                return $json["results"][0]["media_formats"]["gif"]["url"];
            }

        } catch (\Throwable $e) {
            Log::error("Tenor error: " . $e->getMessage());
        }

        return null;
    }

    private function sendGifByQuery($chatId, $query)
    {
        $url = $this->getGifFromTenor($query) ?? null;

        if (!$url) {
            $this->sendMessage($chatId, "No GIF found for '$query'");
            return;
        }

        $this->sendAnimation($chatId, $url);
    }

    private function sendAnimation($chatId, $url)
    {
        Http::post("https://api.telegram.org/bot{$this->botToken}/sendAnimation", [
            "chat_id"   => $chatId,
            "animation" => $url
        ]);
    }

    private function sendMessage($chatId, $text)
    {
        Http::post("https://api.telegram.org/bot{$this->botToken}/sendMessage", [
            "chat_id"    => $chatId,
            "text"       => $text,
            "parse_mode" => "Markdown"
        ]);
    }

    private function sendInlineMenu($chatId)
    {
        $menu = [
            "inline_keyboard" => [
                [
                    ["text" => "ðŸ“ˆ Market", "callback_data" => "market"],
                    ["text" => "ðŸ’¹ Prices", "callback_data" => "prices"]
                ],
                [
                    ["text" => "ðŸŽž GIF", "callback_data" => "gif:trading"]
                ]
            ]
        ];

        Http::post("https://api.telegram.org/bot{$this->botToken}/sendMessage", [
            "chat_id"      => $chatId,
            "text"         => "Quick Actions:",
            "reply_markup" => json_encode($menu)
        ]);
    }

    private function handleCallbackQuery($cb)
    {
        $data = $cb["data"];
        $chatId = $cb["message"]["chat"]["id"];

        Http::post("https://api.telegram.org/bot{$this->botToken}/answerCallbackQuery", [
            "callback_query_id" => $cb["id"]
        ]);

        if ($data === "market") {
            $this->sendMarketWithGif($chatId);
            return;
        }

        if ($data === "prices") {
            $this->sendPrices($chatId);
            return;
        }

        if (str_starts_with($data, "gif:")) {
            $term = explode(":", $data)[1];
            $this->sendGifByQuery($chatId, $term);
        }
    }
}
