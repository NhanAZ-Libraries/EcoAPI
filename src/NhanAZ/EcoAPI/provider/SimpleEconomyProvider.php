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
use NhanAZ\EcoAPI\EconomyProvider;
use NhanAZ\SimpleEconomy\Main as SimpleEconomy;
use pocketmine\player\Player;
use pocketmine\Server;

/**
 * Economy provider for NhanAZ/SimpleEconomy.
 *
 * @see https://poggit.pmmp.io/p/SimpleEconomy
 */
class SimpleEconomyProvider implements EconomyProvider {

	private SimpleEconomy $plugin;

	public function __construct() {
		$plugin = SimpleEconomy::getInstance();
		assert($plugin !== null);
		$this->plugin = $plugin;
	}

	public function getName(): string {
		return "SimpleEconomy";
	}

	public function getCurrencySymbol(): string {
		return $this->plugin->getFormatter()->getSymbol();
	}

	public static function isAvailable(): bool {
		return Server::getInstance()->getPluginManager()->getPlugin("SimpleEconomy") !== null;
	}

	public function getMoney(Player $player, Closure $callback): void {
		$balance = $this->plugin->getMoney($player->getName());
		$callback($balance ?? (float) $this->plugin->getDefaultBalance());
	}

	public function setMoney(Player $player, float $amount, ?Closure $callback = null): void {
		if (!self::isWholeAmount($amount, false)) {
			$callback?->__invoke(false);
			return;
		}
		$success = $this->plugin->setMoney($player->getName(), (int) $amount);
		if ($callback !== null) {
			$callback($success);
		}
	}

	public function addMoney(Player $player, float $amount, ?Closure $callback = null): void {
		if (!self::isWholeAmount($amount, true)) {
			$callback?->__invoke(false);
			return;
		}
		$success = $this->plugin->addMoney($player->getName(), (int) $amount);
		if ($callback !== null) {
			$callback($success);
		}
	}

	public function takeMoney(Player $player, float $amount, ?Closure $callback = null): void {
		if (!self::isWholeAmount($amount, true)) {
			$callback?->__invoke(false);
			return;
		}
		$success = $this->plugin->reduceMoney($player->getName(), (int) $amount);
		if ($callback !== null) {
			$callback($success);
		}
	}

	private static function isWholeAmount(float $amount, bool $positive): bool {
		return is_finite($amount)
			&& ($positive ? $amount > 0 : $amount >= 0)
			&& $amount < PHP_INT_MAX
			&& floor($amount) === $amount;
	}
}
