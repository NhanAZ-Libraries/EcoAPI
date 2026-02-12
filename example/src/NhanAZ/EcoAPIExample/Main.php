<?php

/*
 * EcoAPIExample - Simple example plugin demonstrating EcoAPI usage.
 *
 * This plugin shows how to:
 * 1. Initialize EcoAPI
 * 2. Auto-detect an economy plugin
 * 3. Get a player's balance
 */

declare(strict_types=1);

namespace NhanAZ\EcoAPIExample;

use NhanAZ\EcoAPI\EcoAPI;
use NhanAZ\EcoAPI\EconomyProvider;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Main extends PluginBase implements Listener {

	private EconomyProvider $economy;

	public function onEnable(): void {
		// Step 1: Initialize EcoAPI (call once, safe to call multiple times)
		EcoAPI::init();

		// Step 2: Auto-detect the installed economy plugin
		$provider = EcoAPI::detectProvider();
		if ($provider === null) {
			$this->getLogger()->error("No economy plugin found! Install EconomyAPI, BedrockEconomy, or SimpleEconomy.");
			$this->getServer()->getPluginManager()->disablePlugin($this);
			return;
		}

		// Step 3: Store the provider for later use
		$this->economy = $provider;
		$this->getLogger()->info("Using economy: " . $this->economy->getName());

		// Register event listeners
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	/**
	 * Show balance when a player joins the server.
	 */
	public function onPlayerJoin(PlayerJoinEvent $event): void {
		$player = $event->getPlayer();
		$this->economy->getMoney($player, function (float|int $balance) use ($player): void {
			$symbol = $this->economy->getCurrencySymbol();
			$player->sendMessage("§aWelcome! Your balance: {$symbol}{$balance}");
		});
	}

	/**
	 * /balance command — shows the player's current balance.
	 */
	public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
		if (!($sender instanceof Player)) {
			$sender->sendMessage("This command can only be used in-game.");
			return true;
		}

		$this->economy->getMoney($sender, function (float|int $balance) use ($sender): void {
			$symbol = $this->economy->getCurrencySymbol();
			$sender->sendMessage("§aYour balance: {$symbol}{$balance}");
		});

		return true;
	}
}
