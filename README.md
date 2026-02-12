# EcoAPI

**A simple, lightweight, and unified economy API library for PocketMine-MP plugins.**

EcoAPI allows plugin developers to support multiple economy plugins with a single, clean interface. No need to write separate code for each economy plugin — EcoAPI handles it all.

---

## Table of Contents

- [Features](#features)
- [Supported Economy Plugins](#supported-economy-plugins)
- [Comparison with Other Libraries](#comparison-with-other-libraries)
- [Requirements](#requirements)
- [Installation](#installation)
- [Quick Start](#quick-start)
- [API Reference](#api-reference)
  - [EcoAPI (Main Class)](#ecoapi-main-class)
  - [EconomyProvider (Interface)](#economyprovider-interface)
  - [Exceptions](#exceptions)
- [Usage Examples](#usage-examples)
  - [Example 1: Auto-detect Economy Plugin](#example-1-auto-detect-economy-plugin)
  - [Example 2: Config-based Provider Selection](#example-2-config-based-provider-selection)
  - [Example 3: Shop (Buy Item)](#example-3-shop-buy-item)
  - [Example 4: Player-to-Player Payment](#example-4-player-to-player-payment)
  - [Example 5: Check if Player Can Afford](#example-5-check-if-player-can-afford)
- [Creating Custom Providers](#creating-custom-providers)
- [Example Plugin](#example-plugin)
- [FAQ](#faq)
- [License](#license)

---

## Features

- **Simple API** — Just 4 methods: `getMoney()`, `setMoney()`, `addMoney()`, `takeMoney()`
- **Auto-detect** — Automatically finds the installed economy plugin
- **Async-ready** — Callback-based API works with both sync and async economy plugins
- **Extensible** — Register your own custom providers easily
- **Type-safe** — Full PHP 8.0+ strict typing
- **Lightweight** — Zero external dependencies, minimal code footprint
- **PM5 Compatible** — Built for PocketMine-MP 5.x

## Supported Economy Plugins

| Plugin | Provider Name | Aliases |
|--------|---------------|---------|
| [EconomyAPI](https://poggit.pmmp.io/p/EconomyAPI) | `economyapi` | `economy` |
| [BedrockEconomy](https://poggit.pmmp.io/p/BedrockEconomy) (v4.0+) | `bedrockeconomy` | `bedrock` |
| [SimpleEconomy](https://poggit.pmmp.io/p/SimpleEconomy) | `simpleeconomy` | `simple` |
| Built-in XP (Experience Levels) | `xp` | `exp`, `experience` |

> **Note:** You only need **ONE** economy plugin installed on your server. EcoAPI will auto-detect it.

## Comparison with Other Libraries

| Feature | EcoAPI | libPiggyEconomy | MoneyConnector | libEco | Economizer |
|---------|--------|-----------------|----------------|--------|------------|
| PM5 Support | :white_check_mark: | :white_check_mark: | :x: | :white_check_mark: | :x: |
| Async Support | :white_check_mark: | :white_check_mark: | :x: | Partial | :x: |
| Auto-detect | :white_check_mark: | :x: | :white_check_mark: | :white_check_mark: | :x: |
| Custom Providers | :white_check_mark: | :white_check_mark: | :x: | :x: | :x: |
| EconomyAPI | :white_check_mark: | :white_check_mark: | :white_check_mark: | :white_check_mark: | :white_check_mark: |
| BedrockEconomy | :white_check_mark: | :white_check_mark: | :x: | :white_check_mark: | :x: |
| SimpleEconomy | :white_check_mark: | :white_check_mark: | :x: | :x: | :x: |
| XP as Currency | :white_check_mark: | :white_check_mark: | :x: | :x: | :x: |
| `setMoney()` | :white_check_mark: | :white_check_mark: | :white_check_mark: | :x: | :white_check_mark: |
| Error Handling | :white_check_mark: | :white_check_mark: | :x: | :x: | :x: |
| Example Plugin | :white_check_mark: | :x: | :white_check_mark: | :x: | :x: |
| Detailed README | :white_check_mark: | Partial | Partial | Partial | :x: |

## Requirements

- PocketMine-MP 5.x
- PHP 8.0+
- At least one supported economy plugin installed on your server (or use XP)

## Installation

### Using Poggit (Recommended)

Add EcoAPI as a library dependency in your `.poggit.yml`:

```yaml
projects:
  YourPlugin:
    libs:
      - src: NhanAZ/EcoAPI/EcoAPI
        version: ^1.0.0
```

### Using Composer

```json
{
    "require": {
        "nhanaz/ecoapi": "^1.0.0"
    }
}
```

### Manual Installation

1. Download or clone this repository
2. Copy the `src/NhanAZ/EcoAPI` directory into your plugin's source

---

## Quick Start

### Step 1: Initialize EcoAPI

Call `EcoAPI::init()` once in your plugin's `onEnable()`:

```php
use NhanAZ\EcoAPI\EcoAPI;

public function onEnable(): void {
    EcoAPI::init();
}
```

### Step 2: Get an Economy Provider

```php
// Auto-detect (recommended)
$provider = EcoAPI::detectProvider();
if ($provider === null) {
    $this->getLogger()->error("No economy plugin found!");
    return;
}

// Or by name
$provider = EcoAPI::getProvider("economyapi");
```

### Step 3: Use the Provider

```php
// Get balance
$provider->getMoney($player, function(float|int $balance): void {
    echo "Balance: $balance";
});

// Add money
$provider->addMoney($player, 1000.0, function(bool $success): void {
    echo $success ? "Added!" : "Failed!";
});

// Take money
$provider->takeMoney($player, 500.0, function(bool $success): void {
    echo $success ? "Taken!" : "Failed!";
});

// Set balance
$provider->setMoney($player, 5000.0, function(bool $success): void {
    echo $success ? "Set!" : "Failed!";
});
```

That's it! Your plugin now supports **all** economy plugins automatically.

---

## API Reference

### EcoAPI (Main Class)

`NhanAZ\EcoAPI\EcoAPI` — Static utility class. All methods are static.

| Method | Description | Returns |
|--------|-------------|---------|
| `init()` | Initialize with built-in providers. Call once in `onEnable()`. | `void` |
| `detectProvider()` | Auto-detect first available economy plugin. | `?EconomyProvider` |
| `getProvider(string $name)` | Get provider by name (case-insensitive). | `EconomyProvider` |
| `isAvailable()` | Check if any economy plugin is installed. | `bool` |
| `getAvailableProviders()` | List names of installed economy plugins. | `string[]` |
| `getRegisteredProviders()` | List all registered provider names. | `string[]` |
| `registerProvider(string $class, string ...$names)` | Register a custom provider. | `void` |

### EconomyProvider (Interface)

`NhanAZ\EcoAPI\EconomyProvider` — Interface implemented by all providers.

| Method | Description | Callback |
|--------|-------------|----------|
| `getName(): string` | Provider display name (e.g., "EconomyAPI") | — |
| `getCurrencySymbol(): string` | Currency symbol (e.g., "$") | — |
| `isAvailable(): bool` | Is the economy plugin installed? | — |
| `getMoney(Player, Closure)` | Get player balance | `function(float\|int $balance): void` |
| `setMoney(Player, float, ?Closure)` | Set player balance | `function(bool $success): void` |
| `addMoney(Player, float, ?Closure)` | Add to player balance | `function(bool $success): void` |
| `takeMoney(Player, float, ?Closure)` | Take from player balance | `function(bool $success): void` |

### Exceptions

| Exception | When |
|-----------|------|
| `UnknownProviderException` | Provider name is not registered (typo?) |
| `MissingProviderDependencyException` | Provider exists but economy plugin is not installed |

Both exceptions extend `RuntimeException`.

---

## Usage Examples

### Example 1: Auto-detect Economy Plugin

The simplest way — let EcoAPI find whatever economy plugin is installed:

```php
use NhanAZ\EcoAPI\EcoAPI;
use NhanAZ\EcoAPI\EconomyProvider;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\plugin\PluginBase;

class MyPlugin extends PluginBase implements Listener {
    private EconomyProvider $economy;

    public function onEnable(): void {
        EcoAPI::init();

        $provider = EcoAPI::detectProvider();
        if ($provider === null) {
            $this->getLogger()->error("No economy plugin found!");
            $this->getServer()->getPluginManager()->disablePlugin($this);
            return;
        }

        $this->economy = $provider;
        $this->getLogger()->info("Using: " . $this->economy->getName());
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    public function onJoin(PlayerJoinEvent $event): void {
        $player = $event->getPlayer();
        $this->economy->getMoney($player, function(float|int $balance) use ($player): void {
            $symbol = $this->economy->getCurrencySymbol();
            $player->sendMessage("§aYour balance: {$symbol}{$balance}");
        });
    }
}
```

### Example 2: Config-based Provider Selection

Let server owners choose their preferred economy plugin in config:

config.yml:
```yaml
# Options: auto, economyapi, bedrockeconomy, simpleeconomy, xp
economy-provider: auto
```

Plugin:
```php
use NhanAZ\EcoAPI\EcoAPI;
use NhanAZ\EcoAPI\EconomyProvider;

class MyPlugin extends PluginBase {
    private EconomyProvider $economy;

    public function onEnable(): void {
        $this->saveDefaultConfig();
        EcoAPI::init();

        $providerName = $this->getConfig()->get("economy-provider", "auto");

        if ($providerName === "auto") {
            $provider = EcoAPI::detectProvider();
            if ($provider === null) {
                $this->getLogger()->error("No economy plugin found!");
                $this->getServer()->getPluginManager()->disablePlugin($this);
                return;
            }
            $this->economy = $provider;
        } else {
            try {
                $this->economy = EcoAPI::getProvider($providerName);
            } catch (\Exception $e) {
                $this->getLogger()->error($e->getMessage());
                $this->getServer()->getPluginManager()->disablePlugin($this);
                return;
            }
        }

        $this->getLogger()->info("Economy: " . $this->economy->getName());
    }
}
```

### Example 3: Shop (Buy Item)

```php
public function buyItem(Player $player, string $itemName, int $price): void {
    $this->economy->getMoney($player, function(float|int $balance) use ($player, $itemName, $price): void {
        $symbol = $this->economy->getCurrencySymbol();

        if ($balance < $price) {
            $player->sendMessage("§cNot enough money! You need {$symbol}{$price} but have {$symbol}{$balance}.");
            return;
        }

        $this->economy->takeMoney($player, (float) $price, function(bool $success) use ($player, $itemName, $symbol, $price): void {
            if ($success) {
                // Give the item to the player here
                $player->sendMessage("§aBought {$itemName} for {$symbol}{$price}!");
            } else {
                $player->sendMessage("§cPurchase failed. Please try again.");
            }
        });
    });
}
```

### Example 4: Player-to-Player Payment

```php
public function pay(Player $sender, Player $receiver, float $amount): void {
    $symbol = $this->economy->getCurrencySymbol();

    $this->economy->takeMoney($sender, $amount, function(bool $taken) use ($sender, $receiver, $amount, $symbol): void {
        if (!$taken) {
            $sender->sendMessage("§cPayment failed! Do you have enough money?");
            return;
        }

        $this->economy->addMoney($receiver, $amount, function(bool $added) use ($sender, $receiver, $amount, $symbol): void {
            if ($added) {
                $sender->sendMessage("§aSent {$symbol}{$amount} to " . $receiver->getName());
                $receiver->sendMessage("§aReceived {$symbol}{$amount} from " . $sender->getName());
            } else {
                // Refund the sender if adding to receiver failed
                $this->economy->addMoney($sender, $amount);
                $sender->sendMessage("§cPayment failed. Your money has been refunded.");
            }
        });
    });
}
```

### Example 5: Check if Player Can Afford

```php
public function canAfford(Player $player, float $cost, Closure $callback): void {
    $this->economy->getMoney($player, function(float|int $balance) use ($cost, $callback): void {
        $callback($balance >= $cost);
    });
}

// Usage:
$this->canAfford($player, 500.0, function(bool $canAfford) use ($player): void {
    if ($canAfford) {
        $player->sendMessage("§aYou can afford it!");
    } else {
        $player->sendMessage("§cYou can't afford it.");
    }
});
```

---

## Creating Custom Providers

You can add support for any economy plugin by implementing the `EconomyProvider` interface:

```php
use NhanAZ\EcoAPI\EcoAPI;
use NhanAZ\EcoAPI\EconomyProvider;
use Closure;
use pocketmine\player\Player;
use pocketmine\Server;

class MyCustomProvider implements EconomyProvider {

    public function getName(): string {
        return "MyEconomyPlugin";
    }

    public function getCurrencySymbol(): string {
        return "G";
    }

    public static function isAvailable(): bool {
        return Server::getInstance()->getPluginManager()->getPlugin("MyEconomyPlugin") !== null;
    }

    public function getMoney(Player $player, Closure $callback): void {
        // Replace with your economy plugin's API
        $balance = MyEconomyPlugin::getInstance()->getBalance($player->getName());
        $callback((float) $balance);
    }

    public function setMoney(Player $player, float $amount, ?Closure $callback = null): void {
        $success = MyEconomyPlugin::getInstance()->setBalance($player->getName(), $amount);
        if ($callback !== null) {
            $callback($success);
        }
    }

    public function addMoney(Player $player, float $amount, ?Closure $callback = null): void {
        $success = MyEconomyPlugin::getInstance()->deposit($player->getName(), $amount);
        if ($callback !== null) {
            $callback($success);
        }
    }

    public function takeMoney(Player $player, float $amount, ?Closure $callback = null): void {
        $success = MyEconomyPlugin::getInstance()->withdraw($player->getName(), $amount);
        if ($callback !== null) {
            $callback($success);
        }
    }
}
```

Then register it in your plugin's `onEnable()`:

```php
EcoAPI::init();
EcoAPI::registerProvider(MyCustomProvider::class, "myeconomyplugin", "myeco");

// Now you can use it:
$provider = EcoAPI::getProvider("myeconomyplugin");
```

---

## Example Plugin

A complete working example plugin is included in the [`example/`](example/) directory.

It demonstrates:
- Initializing EcoAPI
- Auto-detecting the economy plugin
- Getting player balance on join
- A `/balance` command

To use it:
1. Build the example plugin with Poggit CI
2. Install it along with any supported economy plugin
3. Join the server — you'll see your balance on join
4. Use `/balance` to check your balance anytime

---

## FAQ

### Q: Do I need to install all economy plugins?

**A:** No! You only need **ONE** economy plugin installed on your server. EcoAPI will auto-detect it. If no economy plugin is installed, you can still use XP (experience levels) as currency.

### Q: Which economy plugin should I recommend to my users?

**A:** [EconomyAPI](https://poggit.pmmp.io/p/EconomyAPI) is the most popular and widely used. [BedrockEconomy](https://poggit.pmmp.io/p/BedrockEconomy) is a modern alternative with async database support.

### Q: What happens if no economy plugin is installed?

**A:** `EcoAPI::detectProvider()` returns `null`. `EcoAPI::getProvider()` throws a `MissingProviderDependencyException`. Always handle these cases in your plugin!

### Q: Can I use XP as currency without any plugin?

**A:** Yes! The XP provider uses player experience levels as currency and requires no additional plugin. Just use `EcoAPI::getProvider("xp")`.

### Q: Why are callbacks used instead of return values?

**A:** Some economy plugins (like BedrockEconomy) use asynchronous database operations. Callbacks ensure compatibility with both sync and async plugins. For sync plugins like EconomyAPI, the callback is invoked immediately — so there's no delay.

### Q: Can the callback parameter be null?

**A:** For `getMoney()`, the callback is **required** (you always want the balance). For `setMoney()`, `addMoney()`, and `takeMoney()`, the callback is **optional** — pass `null` if you don't need to know whether it succeeded.

### Q: Is it safe to call `EcoAPI::init()` multiple times?

**A:** Yes! `init()` only runs once. Subsequent calls are silently ignored.

### Q: How do I add support for Capital?

**A:** Capital uses complex async generators and a DI system. You can create a custom provider for it using the [Creating Custom Providers](#creating-custom-providers) section. Note that bridging Capital's generator-based API with callbacks requires the `await-generator` library.

### Q: Is this library compliant with Poggit rules?

**A:** Yes! EcoAPI follows all [Poggit rules](https://poggit.pmmp.io/rules.edit):
- Uses virion format (rule A6)
- Unique namespace `NhanAZ\EcoAPI` (rule C1a)
- All classes under the namespace (rule C1b)
- Apache 2.0 license included (rule D6)
- No obfuscation (rule B2)
- No startup messages (rule B3)

---

## License

This project is licensed under the **Apache License 2.0** — see the [LICENSE](LICENSE) file for details.
