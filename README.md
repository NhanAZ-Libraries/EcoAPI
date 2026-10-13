# EcoAPI

EcoAPI gives PocketMine-MP plugins one callback based interface for several economy providers. It can be shaded as a virion or loaded through the companion `EcoAPIPlugin` PHAR. The source is licensed under [Apache License 2.0](LICENSE).

## Compatibility and providers

`virion.yml` and `plugin.yml` declare minimum API `5.0.0`, and Composer requires PHP `^8.1`. The full Axolotl-PM 5.x range has not been verified. The [build workflow](.github/workflows/ci.yml) pins the exact server source used for static analysis. Local isolated server tests confirmed that the companion PHAR and example load. Connected headless clients read balances through SimpleEconomy, built-in XP and EconomyAPI in separate tests.

| Provider | Registration names | Runtime requirement |
|---|---|---|
| [EconomyAPI](https://github.com/ACM-PocketMine-MP/EconomyAPI) | `economyapi`, `economy` | An enabled compatible EconomyAPI plugin |
| [BedrockEconomy](https://github.com/cooldogepm/BedrockEconomy) | `bedrockeconomy`, `bedrock` | An enabled compatible BedrockEconomy plugin |
| [SimpleEconomy](https://github.com/NhanAZ-Plugins/SimpleEconomy) | `simpleeconomy`, `simple` | An enabled SimpleEconomy plugin |
| Player XP levels | `xp`, `exp`, `experience` | No economy plugin |

EcoAPI checks availability when a provider is selected. Provider specific money operations have different storage and callback behavior. Test the selected provider with disposable balances before using it on a live server.

An optional provider is available only after its plugin is enabled. A consumer that selects a provider in `onEnable()` should declare the desired plugin names under `softdepend` so they enable first. The example does this for EconomyAPI, BedrockEconomy and SimpleEconomy; when none is ready, it uses built-in XP.

The XP provider treats whole levels as currency. Setting, adding or taking levels keeps the player's progress within the current level. Its callback reports whether Axolotl-PM accepted the level change. An isolated server probe verified level and progress changes with a synthetic Player and the server's ExperienceManager. A connected test player then set 8 levels, added 2 and took 3 through EcoAPI. All three callbacks succeeded. The example read 7 levels before and after a clean server restart, and the test server saved a player data file. This test does not cover XP event cancellation, the Bedrock game UI or other server settings.

SimpleEconomy stores whole units. Its adapter rejects fractional, non-finite, negative and out-of-range amounts; adding or taking money also requires an amount greater than zero. A successful callback reports that SimpleEconomy accepted the immediate in-memory change, while its SQL save continues asynchronously. Balance reads can return the configured default when a player's session has not loaded; that value is not proof of a persisted balance.

EconomyAPI rounds submitted amounts to two decimal places. Its adapter rejects non-finite, negative and subcent amounts before submission. Adding or taking money also requires a positive amount. A successful callback reports the backend return code, while persistence depends on the configured EconomyAPI provider. In an isolated YAML-backed test, a connected player set 42.25, added 8.10 and took 3.05 through EcoAPI. All callbacks succeeded. The example read 47.3 before and after a clean server restart, and `Money.yml` stored 47.3. This test does not cover EconomyAPI's MySQL provider, transaction cancellation or the Bedrock game UI.

The BedrockEconomy adapter accepts finite, nonnegative balances for `setMoney()` and positive amounts for `addMoney()` and `takeMoney()`. It rejects values that would change when represented to two decimal places, and accepts whole units only when the configured currency disables decimals. BedrockEconomy 4.0.4 stores cent values from 01 through 09 incorrectly, so the adapter rejects amounts with those cent components instead of risking an incorrect persisted balance. Rejected amounts call the supplied callback with `false` without submitting a transaction. A success callback reports the underlying BedrockEconomy operation. Isolated SQLite update-failure probes of all three methods returned `false` and kept their prior balances. Connected-player behavior and other failure modes remain unverified.

The tested BedrockEconomy 4.0.4 backend requires Xbox-authenticated player information. With Xbox authentication disabled, it throws during player login before EcoAPI can handle a transaction. Keep authentication enabled when using this provider. This requirement was reproduced on a PocketMine-MP runtime within the backend's published API range, but that attempt did not verify connected transactions or Axolotl-PM compatibility.

In BedrockEconomy 4.0.4, a listener can cancel `TransactionSubmitEvent` before the database operation starts. EcoAPI observes matching canceled updates and reports `false` to the supplied callback exactly once. An isolated PocketMine-MP 5.36.0 server probe used the EcoAPI plugin PHAR from [CI run 38445425063](https://github.com/NhanAZ-Libraries/EcoAPI/actions/runs/38445425063), built from commit `83277ab`. With a synthetic `Player`, the probe canceled `setMoney()`, `addMoney()` and `takeMoney()`; all three events were canceled, each callback ran once with `false`, and the server exited with code `0`. A separate Axolotl-PM 5.49.1 probe confirmed that a canceled `setMoney()` call left the cache and SQLite balance unchanged. These tests do not verify connected-player behavior or persisted balances for canceled `addMoney()` and `takeMoney()` calls.

## Installation and building

For a source based plugin, place a pinned EcoAPI source checkout under `virions/EcoAPI` and declare the virion in your plugin's `devtools.yml`:

```yaml
virions:
  - name: EcoAPI
    version: ^1.0.0
```

The [`example/`](example/) directory contains this declaration. [DevTools](https://github.com/NhanAZ/DevTools) builds the consumer PHAR and shades the EcoAPI namespace. Pin the source revision and follow the [DevTools build guide](https://github.com/NhanAZ/DevTools/blob/main/docs/github-actions.md) for reproducible builds. The repository workflow builds and validates the companion `EcoAPIPlugin` and shaded example PHARs, then uploads them after its checks pass. An artifact is not a stable release.

## Basic usage

Call `EcoAPI::init()` during your plugin's enable phase, then choose an available provider:

```php
use NhanAZ\EcoAPI\EcoAPI;

EcoAPI::init();
$provider = EcoAPI::detectProvider();
if ($provider === null) {
    // No supported provider is available.
    return;
}
$provider->getMoney($player, static function (float|int $balance): void {
    // Use the balance after the provider invokes the callback.
});
```

`getProvider(string $name)` selects a registered provider by name. It throws `UnknownProviderException` for an unknown name and `MissingProviderDependencyException` when that provider is unavailable. The interface also offers `setMoney()`, `addMoney()` and `takeMoney()` with completion callbacks. Check callback results before claiming that an operation succeeded.

EcoAPI does not provide an atomic transfer between two accounts. Do not implement player payments by chaining `takeMoney()` and `addMoney()` and claiming that a later refund makes the operation safe. Use a provider with a verified transaction contract for transfers.

## Custom providers

Implement [`EconomyProvider`](src/NhanAZ/EcoAPI/EconomyProvider.php) and register explicit factory and availability callbacks:

```php
use NhanAZ\EcoAPI\EcoAPI;
use NhanAZ\EcoAPI\EconomyProvider;

EcoAPI::registerProvider(
    static fn(): EconomyProvider => new MyProvider(),
    static fn(): bool => MyProvider::isAvailable(),
    "myprovider", "myalias"
);
```

This registration signature replaces the older class string argument before the first stable release. The explicit factory keeps custom construction in the consuming plugin and lets DevTools shade built in provider references safely. Aliases share one registration, so automatic detection considers that provider once.

## Verification and support

The repository runs [PHPStan at maximum level](phpstan.neon.dist), PHP lint and provider behavior fixtures. Its workflow pins checkout, server setup, DevTools and artifact upload revisions. CI builds and validates the companion PHAR and a shaded consumer example. Isolated server probes exercised SimpleEconomy and BedrockEconomy 4.0.4 transactions with synthetic player objects, then checked persisted SQLite balances. Scoped SQLite triggers rejected `setMoney()`, `addMoney()` and `takeMoney()` updates on test accounts. Each callback reported failure, and the cached and persisted balances stayed unchanged. A separate EconomyAPI 6.0.0-PM5 probe checked callbacks and the YAML balance after shutdown. A connected headless client received the example's join message and `/balance` result through SimpleEconomy. A disposable test plugin also called EcoAPI's `addMoney()`, `takeMoney()` and `setMoney()` methods for a second connected player. All three callbacks succeeded, the client saw the final balance of 1,100 and SQLite retained that balance after shutdown. A separate connected XP probe set, added and took levels. The example read 7 levels before and after restart. Another connected test exercised the EconomyAPI YAML provider with cents and read 47.3 before and after restart. Bedrock game UI, connected BedrockEconomy behavior, other backend settings and other database failure paths remain unverified.

Keep player data backups and a previously verified build during upgrades. For support, use [NhanAZ Discord](https://discord.gg/j2X83ujT6c). See the [LICENSE](LICENSE) file for the full Apache License 2.0 text.
