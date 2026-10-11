<?php

declare(strict_types=1);

namespace EcoAPITest {
	final class PluginDescription {
		public function __construct(private string $version) {
		}

		public function getVersion(): string {
			return $this->version;
		}
	}

	class Plugin {
		public function __construct(public bool $enabled = false, public string $version = "4.0.0") {
		}

		public function isEnabled(): bool {
			return $this->enabled;
		}

		public function getDescription(): PluginDescription {
			return new PluginDescription($this->version);
		}
	}

	final class PluginManager {
		/** @var array<string, object> */
		public array $plugins = [];

		public function getPlugin(string $name): ?object {
			return $this->plugins[$name] ?? null;
		}
	}

	final class ExperienceManager {
		public bool $cancel = false;
		public float $progress = 0.5;

		public function __construct(public int $level) {
		}

		public function getXpLevel(): int {
			return $this->level;
		}

		public function setXpLevel(int $level): bool {
			if ($this->cancel) {
				return false;
			}
			$this->level = $level;
			return true;
		}

		public function addXpLevels(int $amount): bool {
			if ($this->cancel) {
				return false;
			}
			$this->level += $amount;
			return true;
		}

		public function subtractXpLevels(int $amount): bool {
			return $this->addXpLevels(-$amount);
		}
	}
}

namespace pocketmine {
	final class Server {
		public static ?self $instance = null;

		public function __construct(private \EcoAPITest\PluginManager $manager) {
		}

		public static function getInstance(): self {
			return self::$instance ?? throw new \RuntimeException("Test server is not initialized");
		}

		public function getPluginManager(): \EcoAPITest\PluginManager {
			return $this->manager;
		}
	}
}

namespace pocketmine\player {
	final class Player {
		public function __construct(private \EcoAPITest\ExperienceManager $manager) {
		}

		public function getXpManager(): \EcoAPITest\ExperienceManager {
			return $this->manager;
		}

		public function getName(): string {
			return "Tester";
		}

		public function getXuid(): string {
			return "test-xuid";
		}
	}
}

namespace onebone\economyapi {
	final class EconomyAPI extends \EcoAPITest\Plugin {
		public const RET_SUCCESS = 1;
		public const RET_CANCELLED = 2;
		public static ?self $instance = null;
		public bool $accept = true;
		/** @var list<array{string, float}> */
		public array $calls = [];

		public static function getInstance(): self {
			return self::$instance ??= new self();
		}

		public function getMonetaryUnit(): string {
			return "$";
		}

		public function myMoney(\pocketmine\player\Player $player): float {
			return 47.25;
		}

		public function setMoney(\pocketmine\player\Player $player, float $amount): int {
			$this->calls[] = ["set", $amount];
			return $this->accept ? self::RET_SUCCESS : self::RET_CANCELLED;
		}

		public function addMoney(\pocketmine\player\Player $player, float $amount): int {
			$this->calls[] = ["add", $amount];
			return $this->accept ? self::RET_SUCCESS : self::RET_CANCELLED;
		}

		public function reduceMoney(\pocketmine\player\Player $player, float $amount): int {
			$this->calls[] = ["reduce", $amount];
			return $this->accept ? self::RET_SUCCESS : self::RET_CANCELLED;
		}
	}
}

namespace NhanAZ\SimpleEconomy {
	final class Main {
		public static ?self $instance = null;
		public bool $enabled = true;

		/** @var list<array{string, string, int}> */
		public array $calls = [];
		public bool $accept = true;

		public static function getInstance(): ?self {
			return self::$instance;
		}

		public function isEnabled(): bool {
			return $this->enabled;
		}

		public function setMoney(string $name, int $amount): bool {
			$this->calls[] = ["set", $name, $amount];
			return $this->accept;
		}

		public function addMoney(string $name, int $amount): bool {
			$this->calls[] = ["add", $name, $amount];
			return $this->accept;
		}

		public function reduceMoney(string $name, int $amount): bool {
			$this->calls[] = ["reduce", $name, $amount];
			return $this->accept;
		}
	}
}

namespace cooldogedev\BedrockEconomy {
	final class BedrockEconomy extends \EcoAPITest\Plugin {
	}
}

namespace cooldogedev\BedrockEconomy\currency {
	final class Currency {
		public string $symbol = "$";
		public int $defaultAmount = 1;
		public int $defaultDecimals = 50;
		public bool $decimals = true;
	}
}

namespace cooldogedev\BedrockEconomy\database\cache {
	final class CacheEntry {
		public function __construct(public int $amount, public int $decimals) {
		}
	}

	final class Cache {
		public ?CacheEntry $entry = null;

		public function get(string $name): ?CacheEntry {
			return $this->entry;
		}
	}

	final class GlobalCache {
		public static ?Cache $cache = null;

		public static function ONLINE(): Cache {
			return self::$cache ??= new Cache();
		}
	}
}

namespace cooldogedev\BedrockEconomy\api\type {
	final class ClosureAPI {
		/** @var array{int, int}|null */
		public ?array $lastAmount = null;
		public ?string $lastOperation = null;
		public int $calls = 0;

		public function set(string $xuid, string $name, int $amount, int $decimals, \Closure $onSuccess, \Closure $onError): void {
			$this->lastOperation = "set";
			$this->lastAmount = [$amount, $decimals];
			++$this->calls;
			$onSuccess();
		}

		public function add(string $xuid, string $name, int $amount, int $decimals, \Closure $onSuccess, \Closure $onError): void {
			$this->lastOperation = "add";
			$this->lastAmount = [$amount, $decimals];
			++$this->calls;
			$onSuccess();
		}

		public function subtract(string $xuid, string $name, int $amount, int $decimals, \Closure $onSuccess, \Closure $onError): void {
			$this->lastOperation = "subtract";
			$this->lastAmount = [$amount, $decimals];
			++$this->calls;
			$onSuccess();
		}
	}
}

namespace {
	require __DIR__ . "/../src/NhanAZ/EcoAPI/EconomyProvider.php";
	require __DIR__ . "/../src/NhanAZ/EcoAPI/provider/XPProvider.php";
	require __DIR__ . "/../src/NhanAZ/EcoAPI/provider/BedrockEconomyProvider.php";
	require __DIR__ . "/../src/NhanAZ/EcoAPI/provider/EconomyAPIProvider.php";
	require __DIR__ . "/../src/NhanAZ/EcoAPI/provider/SimpleEconomyProvider.php";
	require __DIR__ . "/../src/NhanAZ/EcoAPI/exception/MissingProviderDependencyException.php";
	require __DIR__ . "/../src/NhanAZ/EcoAPI/exception/UnknownProviderException.php";
	require __DIR__ . "/../src/NhanAZ/EcoAPI/EcoAPI.php";

	use cooldogedev\BedrockEconomy\api\type\ClosureAPI;
	use cooldogedev\BedrockEconomy\BedrockEconomy as BedrockBackend;
	use cooldogedev\BedrockEconomy\currency\Currency;
	use cooldogedev\BedrockEconomy\database\cache\CacheEntry;
	use cooldogedev\BedrockEconomy\database\cache\GlobalCache;
	use EcoAPITest\ExperienceManager;
	use EcoAPITest\Plugin;
	use EcoAPITest\PluginManager;
	use NhanAZ\EcoAPI\EcoAPI;
	use NhanAZ\EcoAPI\provider\BedrockEconomyProvider;
	use NhanAZ\EcoAPI\provider\EconomyAPIProvider;
	use NhanAZ\EcoAPI\provider\SimpleEconomyProvider;
	use NhanAZ\EcoAPI\provider\XPProvider;
	use NhanAZ\SimpleEconomy\Main as SimpleEconomy;
	use onebone\economyapi\EconomyAPI as EconomyBackend;
	use pocketmine\player\Player;
	use pocketmine\Server;

	function expect(bool $condition, string $message): void {
		if (!$condition) {
			throw new RuntimeException($message);
		}
	}

	$manager = new ExperienceManager(4);
	$player = new Player($manager);
	$xp = new XPProvider();
	$success = null;
	$xp->takeMoney($player, 5.0, static function (bool $result) use (&$success): void { $success = $result; });
	expect($success === false && $manager->level === 4, "XP overdraft must fail without changing the balance");
	$xp->takeMoney($player, 1.5, static function (bool $result) use (&$success): void { $success = $result; });
	expect($success === false && $manager->level === 4, "Fractional XP withdrawal must fail");
	$xp->takeMoney($player, 2.0, static function (bool $result) use (&$success): void { $success = $result; });
	expect($success === true && $manager->level === 2 && $manager->progress === 0.5, "XP withdrawal must debit two levels without clearing progress");
	$xp->setMoney($player, -1.0, static function (bool $result) use (&$success): void { $success = $result; });
	expect($success === false && $manager->level === 2, "Negative XP balance must fail");
	$xp->addMoney($player, 0.5, static function (bool $result) use (&$success): void { $success = $result; });
	expect($success === false && $manager->level === 2, "Fractional XP deposit must fail");
	$manager->cancel = true;
	$xp->takeMoney($player, 1.0, static function (bool $result) use (&$success): void { $success = $result; });
	expect($success === false && $manager->level === 2 && $manager->progress === 0.5, "Cancelled XP withdrawal must preserve level and progress");
	$xp->addMoney($player, 1.0, static function (bool $result) use (&$success): void { $success = $result; });
	expect($success === false && $manager->level === 2, "Cancelled XP change must report failure");
	$manager->cancel = false;
	$xp->addMoney($player, 1.0, static function (bool $result) use (&$success): void { $success = $result; });
	expect($success === true && $manager->level === 3 && $manager->progress === 0.5, "XP deposit must credit one level without clearing progress");
	$xp->setMoney($player, 7.0, static function (bool $result) use (&$success): void { $success = $result; });
	expect($success === true && $manager->level === 7 && $manager->progress === 0.5, "Setting XP balance must preserve progress");
	$manager->cancel = true;
	$xp->setMoney($player, 8.0, static function (bool $result) use (&$success): void { $success = $result; });
	expect($success === false && $manager->level === 7 && $manager->progress === 0.5, "Cancelled XP balance change must preserve level and progress");
	$manager->cancel = false;

	$reflection = new ReflectionClass(BedrockEconomyProvider::class);
	/** @var BedrockEconomyProvider $bedrock */
	$bedrock = $reflection->newInstanceWithoutConstructor();
	$currency = new Currency();
	$api = new ClosureAPI();
	$reflection->getProperty("currency")->setValue($bedrock, $currency);
	$reflection->getProperty("api")->setValue($bedrock, $api);
	$balance = null;
	$bedrock->getMoney($player, static function (float|int $result) use (&$balance): void { $balance = $result; });
	expect($balance === 1.5, "Default Bedrock balance with 50 cents must be 1.50");
	GlobalCache::ONLINE()->entry = new CacheEntry(1, 5);
	$bedrock->getMoney($player, static function (float|int $result) use (&$balance): void { $balance = $result; });
	expect($balance === 1.05, "Cached Bedrock balance with five cents must be 1.05");
	$bedrock->setMoney($player, 1.5, static function (bool $result) use (&$success): void { $success = $result; });
	expect($success === true && $api->lastAmount === [1, 50], "Bedrock 1.50 must send 50 cents to the API");
	$bedrock->setMoney($player, 1.10, static function (bool $result) use (&$success): void { $success = $result; });
	expect($success === true && $api->lastAmount === [1, 10], "Bedrock 1.10 must send ten cents to the API");
	foreach ([NAN, INF, -INF, -1.0, 0.001, 1.005, 0.01, 1.05, 3.09, (float) PHP_INT_MAX] as $invalid) {
		foreach (["setMoney", "addMoney", "takeMoney"] as $method) {
			$before = $api->calls;
			$success = null;
			$bedrock->{$method}($player, $invalid, static function (bool $result) use (&$success): void { $success = $result; });
			expect($success === false && $api->calls === $before, "Bedrock must reject lossy or invalid amounts before mutation");
		}
	}
	foreach (["addMoney", "takeMoney"] as $method) {
		$before = $api->calls;
		$success = null;
		$bedrock->{$method}($player, 0.0, static function (bool $result) use (&$success): void { $success = $result; });
		expect($success === false && $api->calls === $before, "Bedrock transfer amounts must be positive");
	}
	$bedrock->setMoney($player, 0.0, static function (bool $result) use (&$success): void { $success = $result; });
	expect($success === true && $api->lastOperation === "set" && $api->lastAmount === [0, 0], "Bedrock may set a zero balance");
	$bedrock->addMoney($player, 0.10, static function (bool $result) use (&$success): void { $success = $result; });
	expect($success === true && $api->lastOperation === "add" && $api->lastAmount === [0, 10], "Bedrock add must forward ten cents");
	$bedrock->takeMoney($player, 2.75, static function (bool $result) use (&$success): void { $success = $result; });
	expect($success === true && $api->lastOperation === "subtract" && $api->lastAmount === [2, 75], "Bedrock take must forward dollars and cents");
	$currency->decimals = false;
	$before = $api->calls;
	$bedrock->setMoney($player, 1.25, static function (bool $result) use (&$success): void { $success = $result; });
	expect($success === false && $api->calls === $before, "Whole-unit Bedrock currency must reject cents");
	$bedrock->setMoney($player, 2.0, static function (bool $result) use (&$success): void { $success = $result; });
	expect($success === true && $api->lastAmount === [2, 0], "Whole-unit Bedrock currency must accept whole units");
	$currency->decimals = true;

	$economyBackend = EconomyBackend::getInstance();
	$economyProvider = new EconomyAPIProvider();
	foreach ([NAN, INF, -INF, -1.0, 0.001, 1.005] as $invalid) {
		foreach (["setMoney", "addMoney", "takeMoney"] as $method) {
			$before = count($economyBackend->calls);
			$success = null;
			$economyProvider->{$method}($player, $invalid, static function (bool $result) use (&$success): void { $success = $result; });
			expect($success === false && count($economyBackend->calls) === $before, "EconomyAPI must reject invalid amounts before mutation");
		}
	}
	foreach (["addMoney", "takeMoney"] as $method) {
		$before = count($economyBackend->calls);
		$economyProvider->{$method}($player, 0.0, static function (bool $result) use (&$success): void { $success = $result; });
		expect($success === false && count($economyBackend->calls) === $before, "EconomyAPI transfers must be positive");
	}
	$economyProvider->setMoney($player, 0.0, static function (bool $result) use (&$success): void { $success = $result; });
	$economyProvider->addMoney($player, 0.10, static function (bool $result) use (&$success): void { $success = $result; });
	$economyProvider->takeMoney($player, 3.05, static function (bool $result) use (&$success): void { $success = $result; });
	expect($success === true && $economyBackend->calls === [["set", 0.0], ["add", 0.10], ["reduce", 3.05]], "EconomyAPI must receive valid cent amounts unchanged");
	$economyBackend->accept = false;
	$economyProvider->takeMoney($player, 1.0, static function (bool $result) use (&$success): void { $success = $result; });
	expect($success === false, "EconomyAPI backend cancellation must reach the callback");
	$economyBackend->accept = true;

	SimpleEconomy::$instance = $simpleEconomy = new SimpleEconomy();
	$simpleProvider = new SimpleEconomyProvider();
	foreach ([NAN, INF, -INF, -1.0, 0.5, 1.5, (float) PHP_INT_MAX] as $invalid) {
		foreach (["setMoney", "addMoney", "takeMoney"] as $method) {
			$before = count($simpleEconomy->calls);
			$success = null;
			$simpleProvider->{$method}($player, $invalid, static function (bool $result) use (&$success): void { $success = $result; });
			expect($success === false && count($simpleEconomy->calls) === $before, "SimpleEconomy must reject invalid whole-unit amounts before mutation");
		}
	}
	foreach (["addMoney", "takeMoney"] as $method) {
		$before = count($simpleEconomy->calls);
		$success = null;
		$simpleProvider->{$method}($player, 0.0, static function (bool $result) use (&$success): void { $success = $result; });
		expect($success === false && count($simpleEconomy->calls) === $before, "SimpleEconomy transfer amounts must be positive");
	}
	$simpleProvider->setMoney($player, 0.0, static function (bool $result) use (&$success): void { $success = $result; });
	expect($success === true && $simpleEconomy->calls === [["set", "Tester", 0]], "SimpleEconomy may set a zero balance exactly");
	$simpleProvider->addMoney($player, 3.0, static function (bool $result) use (&$success): void { $success = $result; });
	expect($success === true && $simpleEconomy->calls[1] === ["add", "Tester", 3], "SimpleEconomy must receive exact whole units");
	$simpleEconomy->accept = false;
	$simpleProvider->takeMoney($player, 2.0, static function (bool $result) use (&$success): void { $success = $result; });
	expect($success === false && $simpleEconomy->calls[2] === ["reduce", "Tester", 2], "SimpleEconomy rejection must reach the callback");

	$pluginRegistry = new PluginManager();
	Server::$instance = new Server($pluginRegistry);
	$pluginRegistry->plugins["EconomyAPI"] = new Plugin(true);
	expect(!EconomyAPIProvider::isAvailable(), "Unrelated plugin named EconomyAPI must not be selected");
	$economyPlugin = $economyBackend;
	$economyPlugin->enabled = false;
	$pluginRegistry->plugins["EconomyAPI"] = $economyPlugin;
	expect(!EconomyAPIProvider::isAvailable(), "Loaded but disabled EconomyAPI must not be selected");
	$economyPlugin->enabled = true;
	expect(EconomyAPIProvider::isAvailable(), "Enabled EconomyAPI must be selectable");
	$pluginRegistry->plugins["BedrockEconomy"] = new Plugin(true, "4.0.0");
	expect(!BedrockEconomyProvider::isAvailable(), "Unrelated plugin named BedrockEconomy must not be selected");
	$bedrockPlugin = new BedrockBackend(false, "4.0.0");
	$pluginRegistry->plugins["BedrockEconomy"] = $bedrockPlugin;
	expect(!BedrockEconomyProvider::isAvailable(), "Loaded but disabled BedrockEconomy must not be selected");
	$bedrockPlugin->enabled = true;
	expect(BedrockEconomyProvider::isAvailable(), "Enabled compatible BedrockEconomy must be selectable");
	$bedrockPlugin->version = "3.9.9";
	expect(!BedrockEconomyProvider::isAvailable(), "Unsupported BedrockEconomy must not be selected");
	$pluginRegistry->plugins["SimpleEconomy"] = $simpleEconomy;
	SimpleEconomy::$instance = null;
	expect(!SimpleEconomyProvider::isAvailable(), "Loaded SimpleEconomy without an initialized singleton must not be selected");
	try {
		new SimpleEconomyProvider();
		throw new RuntimeException("Uninitialized SimpleEconomy provider was constructed");
	} catch (\NhanAZ\EcoAPI\exception\MissingProviderDependencyException) {
	}
	SimpleEconomy::$instance = $simpleEconomy;
	$simpleEconomy->enabled = false;
	expect(!SimpleEconomyProvider::isAvailable(), "Disabled SimpleEconomy must not be selected");
	$simpleEconomy->enabled = true;
	expect(SimpleEconomyProvider::isAvailable(), "Enabled initialized SimpleEconomy must be selectable");

	EcoAPI::registerProvider(static fn(): XPProvider => new XPProvider(), static fn(): bool => true, "testxp", "xp-alias");
	expect(EcoAPI::getProvider("XP-ALIAS") instanceof XPProvider, "Provider alias must resolve without dynamic class construction");
	expect(EcoAPI::detectProvider() instanceof XPProvider, "Available custom provider must be detected");
	expect(EcoAPI::getAvailableProviders() === ["testxp"], "Aliases must not duplicate available providers");
	$unavailableFactoryCalled = false;
	EcoAPI::registerProvider(
		static function () use (&$unavailableFactoryCalled): XPProvider {
			$unavailableFactoryCalled = true;
			return new XPProvider();
		},
		static fn(): bool => false,
		"disabled"
	);
	try {
		EcoAPI::getProvider("disabled");
		throw new RuntimeException("Unavailable provider was constructed");
	} catch (\NhanAZ\EcoAPI\exception\MissingProviderDependencyException) {
	}
	expect(!$unavailableFactoryCalled, "Unavailable provider factory must not run");

	echo "PASS: XP, SimpleEconomy, EconomyAPI and Bedrock amount guards, provider factory, aliases and enabled-state availability\n";
}
