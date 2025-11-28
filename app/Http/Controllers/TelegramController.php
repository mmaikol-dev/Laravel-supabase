<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TelegramController extends Controller
{
    // ===== CONFIGURATION =====
    private $botToken     = "7701266590:AAHIN9ZH88CbmoZFRie1s4Y7zUZAd5nc_yA";
    private $botUsername  = "batmantrial_bot";
    private $webhookUrl   = "https://sitebase.co.ke/telegram/webhook";
    private $tenorKey     = "AIzaSyAXpuj14X0QHUcarzStdsKrfZ4Ahy-fIPA";
    private $coingeckoBase = "https://api.coingecko.com/api/v3";
    
    // Trading Bot Configuration
    private $adminUsername = 'traderisrfx';
    private $channelLink = 'https://t.me/+JbJ2MH9hHWhlMWM0';
    private $vipGroupLink = 'https://t.me/pulsetradenetworkvip';
    private $vvipGroupLink = 'https://t.me/kenyantraderscircle';
    private $brokerLink = 'https://portal.blueberrymarkets.com/en/sign-up?referralCode=cjwdz5yr03';
    
    private $trialDays = 7;
    private $adminChatId = "YOUR_ADMIN_CHAT_ID"; // Replace with actual admin ID
    
    // GIF Categories
    private $gifCategories = [
        'welcome' => 'batman welcome',
        'celebration' => 'celebration trading',
        'thinking' => 'thinking analysis',
        'charts' => 'trading charts',
        'rocket' => 'rocket moon',
        'money' => 'money rain',
        'fire' => 'fire trading',
        'victory' => 'victory celebration'
    ];
    // =========================

    public function __construct()
    {
        Log::info("TelegramController loaded", [
            'token'     => substr($this->botToken, 0, 10) . '...',
            'username'  => $this->botUsername,
            'webhook'   => $this->webhookUrl
        ]);
    }

    public function webhook(Request $request)
    {
        Log::info("Webhook update received", $request->all());

        // Respond instantly to Telegram
        response("OK", 200)->send();

        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }

        $update = $request->all();

        // Handle callback queries (button clicks)
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
        $user    = $message["from"];

        // Save user interaction
        $this->saveUserData($user, $message);
        $this->logInteraction($chatId, $text);

        // Handle commands
        if (str_starts_with($text, "/")) {
            $this->handleCommand($chatId, $text, $message);
            return;
        }

        // Handle stickers
        if (isset($message["sticker"])) {
            $this->sendMessage($chatId, "🔥 Nice sticker! Use /help to see what I can do.");
            return;
        }

        // Keyword detection for smart responses
        $this->handleKeywordDetection($chatId, strtolower($text));
    }

    private function handleCommand($chatId, $cmd, $message = null)
    {
        $parts = explode(" ", trim($cmd));
        $command = $parts[0];
        $args = array_slice($parts, 1);

        switch ($command) {
            case "/start":
                // Check for referral code
                $referralCode = $args[0] ?? null;
                if ($referralCode) {
                    $this->processReferral($chatId, $referralCode);
                }
                $this->sendWelcomeMessage($chatId);
                break;

            case "/help":
                $this->sendHelpMessage($chatId);
                break;

            case "/market":
                $this->sendMarketWithGif($chatId);
                break;

            case "/prices":
                $this->sendPrices($chatId);
                break;

            case "/packages":
                $this->sendPackagesInfo($chatId);
                break;

            case "/trial":
                $this->handleTrialRequest($chatId);
                break;

            case "/referral":
                $this->sendReferralInfo($chatId);
                break;

            case "/contact":
                $this->sendContactAdmin($chatId);
                break;

            case "/account":
                $this->sendOpenAccountInfo($chatId);
                break;

            case "/calculator":
                $this->sendRiskCalculator($chatId);
                break;

            case "/guide":
                $this->sendTradingGuide($chatId);
                break;

            case "/mystats":
                $this->sendUserStats($chatId);
                break;

            case "/menu":
                $this->sendPersistentKeyboard($chatId);
                break;

            case "/hidemenu":
                $this->removeKeyboard($chatId);
                break;

            case "/gif":
                $query = implode(" ", $args) ?: "trading";
                $this->sendGifByQuery($chatId, $query);
                break;

            default:
                $this->sendMessage($chatId, "❓ Unknown command. Try /help to see available commands.");
        }
    }

    private function sendWelcomeMessage($chatId)
    {
        $welcomeText = "🦇 *Welcome to Gotham Trading Network!*\n\n"
            . "Your gateway to professional trading signals and market analysis.\n\n"
            . "🎯 *What We Offer:*\n"
            . "• Daily trading signals (Forex, Crypto, Stocks)\n"
            . "• Real-time market analysis\n"
            . "• Risk management tools\n"
            . "• Educational resources\n"
            . "• Community support\n\n"
            . "🚀 *Get Started:*\n"
            . "1️⃣ Join our free channel\n"
            . "2️⃣ Try 7-day premium trial\n"
            . "3️⃣ Upgrade to VIP access\n\n"
            . "Use the menu below or type /help!";

        $this->sendMessage($chatId, $welcomeText);
        $this->sendGifByCategory($chatId, 'welcome');
        $this->sendPersistentKeyboard($chatId);
        $this->sendMainMenu($chatId);
    }

    private function sendHelpMessage($chatId)
    {
        $helpText = "📘 *Available Commands:*\n\n"
            . "*Trading:*\n"
            . "/market - Market overview with live data\n"
            . "/prices - Crypto prices (BTC, ETH, etc)\n"
            . "/calculator - Position size calculator\n"
            . "/account - Open trading account ($50 bonus)\n\n"
            . "*Membership:*\n"
            . "/packages - View pricing plans\n"
            . "/trial - Start 7-day free trial\n"
            . "/referral - Earn with referrals\n\n"
            . "*Resources:*\n"
            . "/guide - Download trading guide\n"
            . "/mystats - View your statistics\n"
            . "/contact - Contact admin\n"
            . "/gif <search> - Search for GIFs\n\n"
            . "*Menu:*\n"
            . "/menu - Show persistent menu buttons\n"
            . "/hidemenu - Hide menu buttons\n\n"
            . "💬 Admin: @{$this->adminUsername}";

        $this->sendMessage($chatId, $helpText);
    }

    private function sendPackagesInfo($chatId)
    {
        $packagesText = "💎 *MEMBERSHIP PACKAGES*\n\n"
            . "🆓 *FREE TIER*\n"
            . "• 3 daily signals\n"
            . "• Public channel access\n"
            . "• Basic market updates\n"
            . "Price: FREE\n\n"
            . "⭐ *PREMIUM*\n"
            . "• 5-8 daily signals\n"
            . "• Priority support\n"
            . "• Market analysis\n"
            . "• Risk calculator\n"
            . "Price: $49/month\n\n"
            . "💎 *VIP*\n"
            . "• 8-12 daily signals\n"
            . "• Private group access\n"
            . "• 1-on-1 mentorship\n"
            . "• Weekly webinars\n"
            . "Price: $99/month\n\n"
            . "👑 *VVIP* (Limited to 50)\n"
            . "• 12-15 daily signals\n"
            . "• Personal trading coach\n"
            . "• Portfolio review\n"
            . "• Lifetime support\n"
            . "Price: $199/month\n\n"
            . "🎁 Start with 7-day FREE trial!";

        $this->sendMessage($chatId, $packagesText);
        
        $keyboard = [
            "inline_keyboard" => [
                [
                    ["text" => "🎯 Start Free Trial", "callback_data" => "start_trial"],
                    ["text" => "⭐ Go Premium", "callback_data" => "upgrade_premium"]
                ],
                [
                    ["text" => "💎 VIP Info", "callback_data" => "vip_info"],
                    ["text" => "👑 VVIP Info", "callback_data" => "vvip_info"]
                ],
                [
                    ["text" => "📊 Broker Signup ($50 Bonus)", "url" => $this->brokerLink]
                ]
            ]
        ];

        $this->sendMessageWithKeyboard($chatId, "Choose your path:", $keyboard);
    }

    private function handleTrialRequest($chatId)
    {
        // Check if user already used trial
        $hasUsedTrial = DB::table('telegram_trials')
            ->where('chat_id', $chatId)
            ->exists();

        if ($hasUsedTrial) {
            $this->sendMessage($chatId, "⚠️ You've already used your free trial.\n\nUpgrade to Premium for full access! Use /packages to see options.");
            return;
        }

        // Activate trial
        $expiryDate = Carbon::now()->addDays($this->trialDays);
        
        DB::table('telegram_trials')->insert([
            'chat_id' => $chatId,
            'started_at' => Carbon::now(),
            'expires_at' => $expiryDate,
            'created_at' => Carbon::now()
        ]);

        DB::table('telegram_users')
            ->where('chat_id', $chatId)
            ->update([
                'membership_tier' => 'trial',
                'trial_expires_at' => $expiryDate
            ]);

        $trialText = "🎉 *TRIAL ACTIVATED!*\n\n"
            . "Welcome to Premium access for 7 days!\n\n"
            . "✅ *You now have:*\n"
            . "• 8 daily trading signals\n"
            . "• Priority support\n"
            . "• Advanced market analysis\n"
            . "• Risk management tools\n\n"
            . "📅 Trial expires: " . $expiryDate->format('M d, Y') . "\n\n"
            . "🔗 Join VIP group: {$this->vipGroupLink}\n\n"
            . "💡 Love it? Upgrade before trial ends!";

        $this->sendMessage($chatId, $trialText);
        $this->sendGifByCategory($chatId, 'celebration');

        // Notify admin
        $this->notifyAdmin("🆕 New trial user: $chatId");
    }

    private function sendReferralInfo($chatId)
    {
        $user = DB::table('telegram_users')->where('chat_id', $chatId)->first();
        $referralCode = $user->referral_code ?? $this->generateReferralCode($chatId);
        
        $referralCount = DB::table('telegram_referrals')
            ->where('referrer_id', $chatId)
            ->count();

        $referralText = "🎁 *REFERRAL PROGRAM*\n\n"
            . "Your code: `{$referralCode}`\n"
            . "Share: https://t.me/{$this->botUsername}?start={$referralCode}\n\n"
            . "📊 *Your Stats:*\n"
            . "Total referrals: {$referralCount}\n\n"
            . "🏆 *Rewards:*\n"
            . "5 refs → 1 month Premium FREE\n"
            . "10 refs → 2 months VIP FREE\n"
            . "25 refs → 6 months VVIP FREE\n\n"
            . "💰 Each referral also earns you credits!";

        $this->sendMessage($chatId, $referralText);
    }

    private function sendRiskCalculator($chatId)
    {
        $calcText = "🧮 *RISK CALCULATOR*\n\n"
            . "Calculate your position size safely!\n\n"
            . "*Example:*\n"
            . "Account: $1,000\n"
            . "Risk: 2% = $20\n"
            . "Stop Loss: 50 pips\n\n"
            . "Position Size: 0.4 lots\n\n"
            . "📚 Formula:\n"
            . "`Risk $ ÷ (Stop Loss × Pip Value)`\n\n"
            . "🎓 Pro Tip: Never risk more than 2% per trade!";

        $this->sendMessage($chatId, $calcText);
        
        $keyboard = [
            "inline_keyboard" => [
                [
                    ["text" => "📖 Learn More", "callback_data" => "learn_risk"],
                    ["text" => "📊 Trading Guide", "callback_data" => "get_guide"]
                ]
            ]
        ];
        
        $this->sendMessageWithKeyboard($chatId, "Need detailed guide?", $keyboard);
    }

    private function sendUserStats($chatId)
    {
        $user = DB::table('telegram_users')->where('chat_id', $chatId)->first();
        
        if (!$user) {
            $this->sendMessage($chatId, "No stats available yet. Start using the bot!");
            return;
        }

        $referralCount = DB::table('telegram_referrals')
            ->where('referrer_id', $chatId)
            ->count();

        $interactionCount = DB::table('telegram_interactions')
            ->where('chat_id', $chatId)
            ->count();

        $memberSince = Carbon::parse($user->created_at)->diffForHumans();

        $statsText = "📊 *YOUR STATISTICS*\n\n"
            . "👤 User ID: {$chatId}\n"
            . "📅 Member since: {$memberSince}\n"
            . "🎖 Tier: " . strtoupper($user->membership_tier ?? 'free') . "\n"
            . "💬 Messages: {$interactionCount}\n"
            . "👥 Referrals: {$referralCount}\n\n";

        if ($user->trial_expires_at) {
            $trialStatus = Carbon::parse($user->trial_expires_at)->isFuture() 
                ? "Active until " . Carbon::parse($user->trial_expires_at)->format('M d')
                : "Expired";
            $statsText .= "🎁 Trial: {$trialStatus}\n";
        }

        $this->sendMessage($chatId, $statsText);
    }

    private function sendMainMenu($chatId)
    {
        $keyboard = [
            "inline_keyboard" => [
                [
                    ["text" => "📈 Market Updates", "callback_data" => "market"],
                    ["text" => "💹 Live Prices", "callback_data" => "prices"]
                ],
                [
                    ["text" => "🎯 Start Free Trial", "callback_data" => "start_trial"],
                    ["text" => "💎 View Packages", "callback_data" => "packages"]
                ],
                [
                    ["text" => "🎁 Referral Program", "callback_data" => "referral"],
                    ["text" => "🧮 Risk Calculator", "callback_data" => "calculator"]
                ],
                [
                    ["text" => "📢 Join Channel", "url" => $this->channelLink],
                    ["text" => "💬 Contact Admin", "url" => "https://t.me/{$this->adminUsername}"]
                ],
                [
                    ["text" => "📊 Open Trading Account ($50 Bonus)", "url" => $this->brokerLink]
                ]
            ]
        ];

        $this->sendMessageWithKeyboard($chatId, "⚡ *Quick Actions:*", $keyboard);
    }

    private function handleCallbackQuery($cb)
    {
        $data = $cb["data"];
        $chatId = $cb["message"]["chat"]["id"];

        // Answer callback to remove loading state
        Http::post("https://api.telegram.org/bot{$this->botToken}/answerCallbackQuery", [
            "callback_query_id" => $cb["id"],
            "text" => "Processing..."
        ]);

        switch ($data) {
            case "market":
                $this->sendMarketWithGif($chatId);
                break;
            case "prices":
                $this->sendPrices($chatId);
                break;
            case "start_trial":
                $this->handleTrialRequest($chatId);
                break;
            case "packages":
                $this->sendPackagesInfo($chatId);
                break;
            case "referral":
                $this->sendReferralInfo($chatId);
                break;
            case "calculator":
                $this->sendRiskCalculator($chatId);
                break;
            case "vip_info":
                $this->sendVIPInfo($chatId);
                break;
            case "vvip_info":
                $this->sendVVIPInfo($chatId);
                break;
            case "get_guide":
                $this->sendTradingGuide($chatId);
                break;
            case "upgrade_premium":
                $this->sendUpgradeInfo($chatId);
                break;
            default:
                if (str_starts_with($data, "gif:")) {
                    $term = explode(":", $data)[1];
                    $this->sendGifByQuery($chatId, $term);
                }
        }
    }

    private function handleKeywordDetection($chatId, $text)
    {
        // Handle persistent keyboard button clicks
        $buttonMappings = [
            '📈 market' => 'market',
            '💎 packages' => 'packages',
            '📊 open account' => 'open_account',
            '🎯 free trial' => 'trial',
            '✍️ write me' => 'contact_admin',
            '📊 my stats' => 'stats',
            '🧮 calculator' => 'calculator',
            '📖 guide' => 'guide',
            '❓ help' => 'help',
        ];

        foreach ($buttonMappings as $button => $action) {
            if (str_contains($text, $button)) {
                switch ($action) {
                    case 'market':
                        $this->sendMarketWithGif($chatId);
                        return;
                    case 'open_account':
                        $this->sendOpenAccountInfo($chatId);
                        return;
                    case 'packages':
                        $this->sendPackagesInfo($chatId);
                        return;
                    case 'trial':
                        $this->handleTrialRequest($chatId);
                        return;
                    case 'contact_admin':
                        $this->sendContactAdmin($chatId);
                        return;
                    case 'stats':
                        $this->sendUserStats($chatId);
                        return;
                    case 'calculator':
                        $this->sendRiskCalculator($chatId);
                        return;
                    case 'guide':
                        $this->sendTradingGuide($chatId);
                        return;
                    case 'help':
                        $this->sendHelpMessage($chatId);
                        return;
                }
            }
        }

        // Regular keyword detection
        $keywords = [
            'signal' => "📊 Want trading signals? Try our 7-day FREE trial! Use /trial",
            'vip' => "💎 Interested in VIP? Check /packages for all options!",
            'trial' => "🎁 Start your FREE 7-day trial now! Use /trial command",
            'price' => "💹 Check live prices with /prices or /market",
            'help' => "📘 Use /help to see all available commands",
            'refer' => "🎁 Earn rewards with our referral program! Use /referral",
            'account' => "📊 Open your trading account and get $50 bonus!",
            'contact' => "✍️ Need help? Contact @{$this->adminUsername}",
        ];

        foreach ($keywords as $keyword => $response) {
            if (str_contains($text, $keyword)) {
                $this->sendMessage($chatId, $response);
                return;
            }
        }

        // Default response
        $this->sendMessage($chatId, "👋 Hi! I didn't quite understand that. Try /help to see what I can do!");
    }

    // ===== MARKET DATA FUNCTIONS =====

    private function sendMarketWithGif($chatId)
    {
        $prices = $this->getPrices(["bitcoin", "ethereum", "binancecoin"]);

        $text = "📈 *CRYPTO MARKET UPDATE*\n\n"
            . "🟠 BTC: $" . number_format($prices["bitcoin"], 2) . "\n"
            . "🔷 ETH: $" . number_format($prices["ethereum"], 2) . "\n"
            . "🟡 BNB: $" . number_format($prices["binancecoin"], 2) . "\n\n"
            . "⏰ Updated: " . Carbon::now()->format('H:i') . " UTC\n"
            . "📊 Use /prices for more coins";

        $this->sendMessage($chatId, $text);
        $this->sendGifByCategory($chatId, 'charts');
    }

    private function sendPrices($chatId)
    {
        $prices = $this->getPrices(["bitcoin","ethereum","litecoin","ripple","cardano"]);

        $reply = "💹 *LIVE CRYPTO PRICES*\n\n";
        $emojis = [
            'bitcoin' => '🟠',
            'ethereum' => '🔷',
            'litecoin' => '⚪',
            'ripple' => '🔵',
            'cardano' => '🔷'
        ];

        foreach ($prices as $k => $v) {
            $emoji = $emojis[$k] ?? '💰';
            $reply .= $emoji . " " . strtoupper($k) . ": $" . number_format($v, 2) . "\n";
        }

        $reply .= "\n⏰ " . Carbon::now()->format('H:i') . " UTC";

        $this->sendMessage($chatId, $reply);
    }

    private function getPrices($ids)
    {
        return Cache::remember("cg_" . implode("_",$ids), 30, function() use ($ids) {
            try {
                $res = Http::timeout(5)->get($this->coingeckoBase . "/simple/price", [
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

    // ===== GIF FUNCTIONS =====

    private function sendGifByCategory($chatId, $category)
    {
        $query = $this->gifCategories[$category] ?? 'trading';
        $this->sendGifByQuery($chatId, $query);
    }

    private function getGifFromTenor($query)
    {
        try {
            $res = Http::timeout(5)->get("https://tenor.googleapis.com/v2/search", [
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
        $url = $this->getGifFromTenor($query);

        if (!$url) {
            $this->sendMessage($chatId, "🔍 No GIF found for '$query'");
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

    // ===== MESSAGING FUNCTIONS =====

    private function sendMessage($chatId, $text)
    {
        Http::post("https://api.telegram.org/bot{$this->botToken}/sendMessage", [
            "chat_id"    => $chatId,
            "text"       => $text,
            "parse_mode" => "Markdown"
        ]);
    }

    private function sendMessageWithKeyboard($chatId, $text, $keyboard)
    {
        Http::post("https://api.telegram.org/bot{$this->botToken}/sendMessage", [
            "chat_id"      => $chatId,
            "text"         => $text,
            "parse_mode"   => "Markdown",
            "reply_markup" => json_encode($keyboard)
        ]);
    }

    private function sendPersistentKeyboard($chatId)
    {
        // Reply keyboard (buttons in input area)
        $keyboard = [
            "keyboard" => [
                [
                    ["text" => "📈 Market"],
                    ["text" => "💎 Packages"],
                    ["text" => "📊 Open Account"]
                ],
                [
                    ["text" => "🎯 Free Trial"],
                    ["text" => "✍️ Write Me"],
                    ["text" => "📊 My Stats"]
                ],
                [
                    ["text" => "🧮 Calculator"],
                    ["text" => "📖 Guide"],
                    ["text" => "❓ Help"]
                ]
            ],
            "resize_keyboard" => true,
            "persistent" => true
        ];

        Http::post("https://api.telegram.org/bot{$this->botToken}/sendMessage", [
            "chat_id"      => $chatId,
            "text"         => "⚡ *Quick Access Menu Activated!*\n\nUse the buttons below for quick commands.",
            "parse_mode"   => "Markdown",
            "reply_markup" => json_encode($keyboard)
        ]);
    }

    private function removeKeyboard($chatId)
    {
        $keyboard = [
            "remove_keyboard" => true
        ];

        Http::post("https://api.telegram.org/bot{$this->botToken}/sendMessage", [
            "chat_id"      => $chatId,
            "text"         => "Menu removed. Type /start to get it back!",
            "reply_markup" => json_encode($keyboard)
        ]);
    }

    // ===== DATABASE FUNCTIONS =====

    private function saveUserData($user, $message)
    {
        $chatId = $user["id"];
        $username = $user["username"] ?? null;
        $firstName = $user["first_name"] ?? null;
        $lastName = $user["last_name"] ?? null;

        $existing = DB::table('telegram_users')->where('chat_id', $chatId)->exists();
        
        if ($existing) {
            // Update existing user
            DB::table('telegram_users')
                ->where('chat_id', $chatId)
                ->update([
                    'username' => $username,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'last_active_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);
        } else {
            // Insert new user
            DB::table('telegram_users')->insert([
                'chat_id' => $chatId,
                'username' => $username,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'last_active_at' => Carbon::now(),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }
    }

    private function logInteraction($chatId, $message)
    {
        DB::table('telegram_interactions')->insert([
            'chat_id' => $chatId,
            'message' => substr($message, 0, 500),
            'created_at' => Carbon::now()
        ]);
    }

    private function generateReferralCode($chatId)
    {
        $code = 'REF' . strtoupper(substr(md5($chatId . time()), 0, 8));
        
        DB::table('telegram_users')
            ->where('chat_id', $chatId)
            ->update(['referral_code' => $code]);

        return $code;
    }

    private function processReferral($chatId, $referralCode)
    {
        $referrer = DB::table('telegram_users')
            ->where('referral_code', $referralCode)
            ->first();

        if (!$referrer) {
            return;
        }

        // Save referral
        DB::table('telegram_referrals')->insert([
            'referrer_id' => $referrer->chat_id,
            'referred_id' => $chatId,
            'created_at' => Carbon::now()
        ]);

        // Notify referrer
        $this->sendMessage($referrer->chat_id, "🎉 New referral! You earned credits. Use /referral to see your stats.");
    }

    private function notifyAdmin($message)
    {
        if ($this->adminChatId) {
            $this->sendMessage($this->adminChatId, "🔔 *Admin Notification*\n\n" . $message);
        }
    }

    // ===== ADDITIONAL INFO FUNCTIONS =====

    private function sendVIPInfo($chatId)
    {
        $text = "💎 *VIP MEMBERSHIP*\n\n"
            . "✨ *Includes:*\n"
            . "• 8-12 premium signals daily\n"
            . "• Private Telegram group\n"
            . "• Weekly market webinars\n"
            . "• 1-on-1 mentorship sessions\n"
            . "• Priority customer support\n"
            . "• Advanced trading tools\n\n"
            . "💰 Price: $99/month\n\n"
            . "🎁 Try FREE for 7 days with /trial";

        $this->sendMessage($chatId, $text);
        $this->sendGifByCategory($chatId, 'victory');
    }

    private function sendVVIPInfo($chatId)
    {
        $text = "👑 *VVIP ELITE MEMBERSHIP*\n\n"
            . "🌟 *Premium Features:*\n"
            . "• 12-15 expert signals daily\n"
            . "• Personal trading coach\n"
            . "• Portfolio analysis & review\n"
            . "• Exclusive market insights\n"
            . "• Direct access to head trader\n"
            . "• Lifetime priority support\n\n"
            . "⚠️ *Limited to 50 members only*\n\n"
            . "💰 Price: $199/month\n\n"
            . "🔥 Join the elite trading club!";

        $this->sendMessage($chatId, $text);
        $this->sendGifByCategory($chatId, 'rocket');
    }

    private function sendTradingGuide($chatId)
    {
        $this->sendMessage($chatId, "📖 *TRADING GUIDE*\n\nPreparing your comprehensive trading guide...");
        
        // Here you would send actual PDF/document
        $this->sendMessage($chatId, "📚 Guide topics:\n• Risk Management\n• Technical Analysis\n• Trading Psychology\n• Position Sizing\n\n📥 Download full PDF from our channel: {$this->channelLink}");
        
        $this->sendGifByCategory($chatId, 'thinking');
    }

    private function sendUpgradeInfo($chatId)
    {
        $text = "⭐ *UPGRADE TO PREMIUM*\n\n"
            . "Ready to level up your trading?\n\n"
            . "💳 Payment options:\n"
            . "• PayPal\n"
            . "• Crypto (BTC/USDT)\n"
            . "• Bank Transfer\n\n"
            . "📞 Contact @{$this->adminUsername} to upgrade\n\n"
            . "🎁 Or start FREE trial: /trial";

        $this->sendMessage($chatId, $text);
    }

    private function sendContactAdmin($chatId)
    {
        $text = "✍️ *CONTACT ADMIN*\n\n"
            . "Need help or have questions?\n\n"
            . "👤 Reach out to our admin:\n"
            . "📱 @{$this->adminUsername}\n\n"
            . "We're here to help you succeed! 🚀\n\n"
            . "*Response Time:* Usually within 1-2 hours";

        $keyboard = [
            "inline_keyboard" => [
                [
                    ["text" => "💬 Message Admin", "url" => "https://t.me/{$this->adminUsername}"]
                ],
                [
                    ["text" => "📢 Join Channel", "url" => $this->channelLink],
                    ["text" => "👥 VIP Group", "url" => $this->vipGroupLink]
                ]
            ]
        ];

        $this->sendMessageWithKeyboard($chatId, $text, $keyboard);
    }

    private function sendOpenAccountInfo($chatId)
    {
        $text = "📊 *OPEN TRADING ACCOUNT*\n\n"
            . "🎁 *Special Offer:*\n"
            . "Get $50 bonus when you sign up!\n\n"
            . "✨ *Blueberry Markets Benefits:*\n"
            . "• Low spreads from 0.0 pips\n"
            . "• Fast execution\n"
            . "• $0 minimum deposit\n"
            . "• MT4/MT5 platforms\n"
            . "• 24/7 support\n\n"
            . "🔗 Click below to get started:\n\n"
            . "💡 *Pro Tip:* After opening your account, join our signals channel for daily trade setups!";

        $keyboard = [
            "inline_keyboard" => [
                [
                    ["text" => "🚀 Open Account Now ($50 Bonus)", "url" => $this->brokerLink]
                ],
                [
                    ["text" => "📢 Join Signals Channel", "url" => $this->channelLink],
                    ["text" => "❓ Need Help?", "url" => "https://t.me/{$this->adminUsername}"]
                ]
            ]
        ];

        $this->sendMessageWithKeyboard($chatId, $text, $keyboard);
        $this->sendGifByCategory($chatId, 'money');
    }
}