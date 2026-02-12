<?php

/*
 * EcoAPI - Simple and unified economy API for PocketMine-MP
 *
 * Licensed under the Apache License, Version 2.0
 * https://www.apache.org/licenses/LICENSE-2.0
 */

declare(strict_types=1);

namespace NhanAZ\EcoAPI;

use NhanAZ\EcoAPI\exception\MissingProviderDependencyException;
use NhanAZ\EcoAPI\exception\UnknownProviderException;
use NhanAZ\EcoAPI\provider\BedrockEconomyProvider;
use NhanAZ\EcoAPI\provider\EconomyAPIProvider;
use NhanAZ\EcoAPI\provider\SimpleEconomyProvider;
use NhanAZ\EcoAPI\provider\XPProvider;

/**
 * Main entry point for EcoAPI.
 *
 * Provides a unified interface to multiple economy plugins.
 * Call EcoAPI::init() once in your plugin's onEnable(), then use
 * detectProvider() or getProvider() to get an EconomyProvider instance.
 *
 * Usage:
 *   EcoAPI::init();
 *   $provider = EcoAPI::detectProvider();
 *   $provider->getMoney($player, function(float|int $balance): void { ... });
 */
final class EcoAPI {

	/**
	 * @var array<string, class-string<EconomyProvider>>
	 * Maps provider names (lowercase) to their class names.
	 */
	private static array $providers = [];

	/** Whether init() has been called. */
	private static bool $initialized = false;

	/** Prevent instantiation — all methods are static. */
	private function __construct() {
	}

	/**
	 * Initialize EcoAPI with built-in providers.
	 *
	 * Call this once in your plugin's onEnable().
	 * Safe to call multiple times — subsequent calls are ignored.
	 */
	public static function init(): void {
		if (self::$initialized) {
			return;
		}
		self::$initialized = true;

		self::registerProvider(EconomyAPIProvider::class, "economyapi", "economy");
		self::registerProvider(BedrockEconomyProvider::class, "bedrockeconomy", "bedrock");
		self::registerProvider(SimpleEconomyProvider::class, "simpleeconomy", "simple");
		self::registerProvider(XPProvider::class, "xp", "exp", "experience");
	}

	/**
	 * Register a custom economy provider.
	 *
	 * You can register multiple names/aliases for the same provider.
	 * Names are case-insensitive.
	 *
	 * Example:
	 *   EcoAPI::registerProvider(MyProvider::class, "myprovider", "myeco");
	 *
	 * @param class-string<EconomyProvider> $providerClass The provider class
	 * @param string ...$names One or more names/aliases for this provider
	 */
	public static function registerProvider(string $providerClass, string ...$names): void {
		foreach ($names as $name) {
			self::$providers[strtolower($name)] = $providerClass;
		}
	}

	/**
	 * Get an economy provider by name.
	 *
	 * @param string $name The provider name (case-insensitive), e.g., "economyapi"
	 *
	 * @throws UnknownProviderException If the provider name is not registered
	 * @throws MissingProviderDependencyException If the provider's plugin is not installed
	 */
	public static function getProvider(string $name): EconomyProvider {
		$key = strtolower($name);

		if (!isset(self::$providers[$key])) {
			$available = implode(", ", array_keys(self::$providers));
			throw new UnknownProviderException(
				"Unknown economy provider: \"{$name}\". Registered providers: {$available}"
			);
		}

		$class = self::$providers[$key];

		if (!$class::isAvailable()) {
			throw new MissingProviderDependencyException(
				"Economy provider \"{$name}\" is not available. Is the required plugin installed and enabled?"
			);
		}

		return new $class();
	}

	/**
	 * Auto-detect the first available economy provider.
	 *
	 * Checks each registered provider in order and returns the first one
	 * whose economy plugin is installed. Returns null if none found.
	 *
	 * @return EconomyProvider|null The first available provider, or null if none found
	 */
	public static function detectProvider(): ?EconomyProvider {
		$seen = [];
		foreach (self::$providers as $class) {
			if (isset($seen[$class])) {
				continue;
			}
			$seen[$class] = true;

			if ($class::isAvailable()) {
				return new $class();
			}
		}
		return null;
	}

	/**
	 * Check if any economy provider is available on the server.
	 */
	public static function isAvailable(): bool {
		return self::detectProvider() !== null;
	}

	/**
	 * Get a list of all registered provider names.
	 *
	 * @return string[] e.g., ["economyapi", "economy", "bedrockeconomy", "bedrock", ...]
	 */
	public static function getRegisteredProviders(): array {
		return array_keys(self::$providers);
	}

	/**
	 * Get a list of provider names that are currently available (plugin installed).
	 *
	 * Only returns the primary name for each provider (not aliases).
	 *
	 * @return string[] e.g., ["economyapi", "xp"]
	 */
	public static function getAvailableProviders(): array {
		$available = [];
		$seen = [];
		foreach (self::$providers as $name => $class) {
			if (isset($seen[$class])) {
				continue;
			}
			$seen[$class] = true;

			if ($class::isAvailable()) {
				$available[] = $name;
			}
		}
		return $available;
	}
}
