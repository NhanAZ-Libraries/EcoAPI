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
use pocketmine\player\Player;

/**
 * Economy provider using PocketMine-MP's built-in XP (experience) system.
 *
 * Uses player XP levels as currency. No additional plugin required.
 * This is always available since XP is a built-in feature of PocketMine-MP.
 */
class XPProvider implements EconomyProvider {

	public function getName(): string {
		return "XP";
	}

	public function getCurrencySymbol(): string {
		return "XP ";
	}

	public static function isAvailable(): bool {
		return true; // Always available — XP is built into PocketMine-MP
	}

	public function getMoney(Player $player, Closure $callback): void {
		$callback((float) $player->getXpManager()->getXpLevel());
	}

	public function setMoney(Player $player, float $amount, ?Closure $callback = null): void {
		$player->getXpManager()->setXpLevel((int) $amount);
		$player->getXpManager()->setXpProgress(0.0);
		if ($callback !== null) {
			$callback(true);
		}
	}

	public function addMoney(Player $player, float $amount, ?Closure $callback = null): void {
		$player->getXpManager()->addXpLevels((int) $amount);
		if ($callback !== null) {
			$callback(true);
		}
	}

	public function takeMoney(Player $player, float $amount, ?Closure $callback = null): void {
		$current = $player->getXpManager()->getXpLevel();
		$new = max(0, $current - (int) $amount);
		$player->getXpManager()->setXpLevel($new);
		$player->getXpManager()->setXpProgress(0.0);
		if ($callback !== null) {
			$callback(true);
		}
	}
}
