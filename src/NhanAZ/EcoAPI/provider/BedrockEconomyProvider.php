<?php

/*
 * EcoAPI - Simple and unified economy API for PocketMine-MP
 *
 * Licensed under the Apache License, Version 2.0
 * https://www.apache.org/licenses/LICENSE-2.0
 */

declare(strict_types=1);

namespace NhanAZ\EcoAPI\provider;

use Closure;
use cooldogedev\BedrockEconomy\api\BedrockEconomyAPI;
use cooldogedev\BedrockEconomy\api\type\ClosureAPI;
use cooldogedev\BedrockEconomy\BedrockEconomy;
use cooldogedev\BedrockEconomy\currency\Currency;
use cooldogedev\BedrockEconomy\database\cache\GlobalCache;
use NhanAZ\EcoAPI\EconomyProvider;
use pocketmine\player\Player;
use pocketmine\Server;

/**
 * Economy provider for cooldogedev/BedrockEconomy (v4.0+).
 *
 * BedrockEconomy uses asynchronous database operations,
 * so all callbacks may be called on a later tick.
 *
 * @see https://poggit.pmmp.io/p/BedrockEconomy
 */
class BedrockEconomyProvider implements EconomyProvider {

	private ClosureAPI $api;
	private Currency $currency;

	public function __construct() {
		$this->api = BedrockEconomyAPI::CLOSURE();
		$this->currency = BedrockEconomy::getInstance()->getCurrency();
	}

	public function getName(): string {
		return "BedrockEconomy";
	}

	public function getCurrencySymbol(): string {
		return $this->currency->symbol;
	}

	public static function isAvailable(): bool {
		$plugin = Server::getInstance()->getPluginManager()->getPlugin("BedrockEconomy");
		return $plugin !== null
			&& version_compare($plugin->getDescription()->getVersion(), "4.0", ">=");
	}

	public function getMoney(Player $player, Closure $callback): void {
		$entry = GlobalCache::ONLINE()->get($player->getName());
		if ($entry !== null) {
			$callback((float) "{$entry->amount}.{$entry->decimals}");
		} else {
			$callback((float) "{$this->currency->defaultAmount}.{$this->currency->defaultDecimals}");
		}
	}

	public function setMoney(Player $player, float $amount, ?Closure $callback = null): void {
		$decimals = self::extractDecimals($amount);
		$this->api->set(
			$player->getXuid(),
			$player->getName(),
			(int) $amount,
			$decimals,
			function () use ($callback): void {
				if ($callback !== null) {
					$callback(true);
				}
			},
			function () use ($callback): void {
				if ($callback !== null) {
					$callback(false);
				}
			}
		);
	}

	public function addMoney(Player $player, float $amount, ?Closure $callback = null): void {
		$decimals = self::extractDecimals($amount);
		$this->api->add(
			$player->getXuid(),
			$player->getName(),
			(int) $amount,
			$decimals,
			function () use ($callback): void {
				if ($callback !== null) {
					$callback(true);
				}
			},
			function () use ($callback): void {
				if ($callback !== null) {
					$callback(false);
				}
			}
		);
	}

	public function takeMoney(Player $player, float $amount, ?Closure $callback = null): void {
		$decimals = self::extractDecimals($amount);
		$this->api->subtract(
			$player->getXuid(),
			$player->getName(),
			(int) $amount,
			$decimals,
			function () use ($callback): void {
				if ($callback !== null) {
					$callback(true);
				}
			},
			function () use ($callback): void {
				if ($callback !== null) {
					$callback(false);
				}
			}
		);
	}

	/**
	 * Extract the decimal part from a float amount.
	 * e.g., 123.45 => 45, 100.0 => 0
	 */
	private static function extractDecimals(float $amount): int {
		$parts = explode(".", (string) $amount);
		return (int) ($parts[1] ?? 0);
	}
}
