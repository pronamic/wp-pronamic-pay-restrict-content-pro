# Change Log

All notable changes to this project will be documented in this file.

This projects adheres to [Semantic Versioning](http://semver.org/) and [Keep a CHANGELOG](http://keepachangelog.com/).

## [Unreleased][unreleased]
-

## [4.6.0] - 2024-07-24

### Changed

- Requires PHP 8.1. ([4a09f5f](https://github.com/pronamic/wp-pronamic-pay-restrict-content-pro/commit/4a09f5f2daa08fc27fd4eee3d64ca8435ac8a6b3))

### Commits

- Added support for payment method updates. ([c056a4d](https://github.com/pronamic/wp-pronamic-pay-restrict-content-pro/commit/c056a4deef669cc4c21ed9191ca8dc62a9786ae2))

### Composer

- Changed `php` from `>=8.0` to `>=8.1`.

Full set of changes: [`4.5.0...4.6.0`][4.6.0]

[4.6.0]: https://github.com/pronamic/wp-pronamic-pay-restrict-content-pro/compare/v4.5.0...v4.6.0

## [4.5.0] - 2024-05-15

### Changed

- Restrict Content (Pro) memberships updates are now processed to the connected Pronamic Pay subscriptions.
- A failed first payment will no longer result in an accumulation of the Pronamic Pay subscription phases.
- The Pronamic Pay subscription ID is now processed in the Restrict Content (Pro) "Gateway Subscription ID" field for memberships.

### Commits

- Added `rcp_gateway_subscription_id_url` and upgrade old memberships with gateway subscription ID. ([144ed86](https://github.com/pronamic/wp-pronamic-pay-restrict-content-pro/commit/144ed865a591dc9e81b02d534742af847456582b))
- Enable support for price changes, fixes #19. ([39c7cd0](https://github.com/pronamic/wp-pronamic-pay-restrict-content-pro/commit/39c7cd043987b3f3c05a954b0e1a9ff3e374391e))
- Reuse subscription updater class in gateway, more DRY. ([80a8f5d](https://github.com/pronamic/wp-pronamic-pay-restrict-content-pro/commit/80a8f5d87ef86ede3b0d2174898ae29988a656f6))
- Change PHP namespace `RestrictContentPro` to `RestrictContent` and group gateways in `Gateways` namespace. ([991ce74](https://github.com/pronamic/wp-pronamic-pay-restrict-content-pro/commit/991ce7455aacbcc05b5ff5f746b6240d3bc6c065))
- Make library more type safe. ([9ce8236](https://github.com/pronamic/wp-pronamic-pay-restrict-content-pro/commit/9ce823602afad3e6060830e8cd2c53e91ae40e3c))
- Throw an exception if the Restrict Content data does not meet expectations. ([b2516ff](https://github.com/pronamic/wp-pronamic-pay-restrict-content-pro/commit/b2516ffb4e9e40fc4183fda04cfc0918fc7cdb9e))
- Reset the phases so they don't pile up. ([b15a942](https://github.com/pronamic/wp-pronamic-pay-restrict-content-pro/commit/b15a9425def6b2c7a8886719e5cf968eaa14c5c7))

### Composer

- Added `automattic/jetpack-autoloader` `^3.0`.
- Added `woocommerce/action-scheduler` `^3.7`.
- Added `wp-pay-gateways/mollie` `^4.10`.
- Changed `php` from `>=7.4` to `>=8.0`.
- Changed `wp-pay/core` from `^4.6` to `v4.17.0`.
	Release notes: https://github.com/pronamic/wp-pay-core/releases/tag/v4.17.0

Full set of changes: [`4.4.4...4.5.0`][4.5.0]

[4.5.0]: https://github.com/pronamic/wp-pronamic-pay-restrict-content-pro/compare/v4.4.4...v4.5.0

## [4.4.4] - 2024-03-14

### Commits

- Added `wp-slug` to Composer config. ([1100c10](https://github.com/pronamic/wp-pronamic-pay-restrict-content-pro/commit/1100c10e163ee5edd8ba947ca1f949c5f4fc46f4))

Full set of changes: [`4.4.3...4.4.4`][4.4.4]

[4.4.4]: https://github.com/pronamic/wp-pronamic-pay-restrict-content-pro/compare/v4.4.3...v4.4.4

## [4.4.3] - 2024-03-14

### Fixed

- Fixed setting Restrict Content Pro refunded payment status on refunds and chargebacks ([#14](https://github.com/pronamic/wp-pronamic-pay-restrict-content-pro/issues/14)). ([09a28d8](https://github.com/pronamic/wp-pronamic-pay-restrict-content-pro/commit/09a28d8304a477fdd4a0eb8cf43481dc62bce764))
- Fixed updating RCP membership status on subscription status update ([#13](https://github.com/pronamic/wp-pronamic-pay-restrict-content-pro/issues/13)). ([7fe6938](https://github.com/pronamic/wp-pronamic-pay-restrict-content-pro/commit/7fe693819524ac32a81daa5a40280f92b0accb39))

Full set of changes: [`4.4.2...4.4.3`][4.4.3]

[4.4.3]: https://github.com/pronamic/wp-pronamic-pay-restrict-content-pro/compare/v4.4.2...v4.4.3

## [4.4.2] - 2024-02-07

### Changed

- The code further complies with (WordPress) coding standards.

Full set of changes: [`4.4.1...4.4.2`][4.4.2]

[4.4.2]: https://github.com/pronamic/wp-pronamic-pay-restrict-content-pro/compare/v4.4.1...v4.4.2

## [4.4.1] - 2023-09-29

### Fixed

- Fixed duplicate payment status email.

### Commits

- Don't hook `pronamic_pay_update_payment` action to fix duplicate payment status updates. ([8713ba4](https://github.com/pronamic/wp-pronamic-pay-restrict-content-pro/commit/8713ba4f7d2160b4f95f59f59f67cd1c00e0d581))

Full set of changes: [`4.4.0...4.4.1`][4.4.1]

[4.4.1]: https://github.com/pronamic/wp-pronamic-pay-restrict-content-pro/compare/v4.4.0...v4.4.1

## [4.4.0] - 2023-09-11

### Commits

- Merge pull request #9 from pronamic/8-restrict-content-pro-failed-payment-issues ([517714b](https://github.com/pronamic/wp-pronamic-pay-restrict-content-pro/commit/517714b9710730db22b2cc1f218cdfb86be1d75e))
- Don't use `empty()`. ([f21433a](https://github.com/pronamic/wp-pronamic-pay-restrict-content-pro/commit/f21433ac79026935f375f8768f34de9b0c6f4767))
- Only update RCP payment failed/abandoned status for last payment. ([fc9e0ac](https://github.com/pronamic/wp-pronamic-pay-restrict-content-pro/commit/fc9e0ac41240d7fbcfcaa3a7519c5558439b21b4))
- Updated membership status for failed payment if last payment ID meta does not yet exist. ([10ec434](https://github.com/pronamic/wp-pronamic-pay-restrict-content-pro/commit/10ec434beb82a9e1b30ad098a47bef4ff03b61d8))
- Fixed phpcs. ([9bbc53d](https://github.com/pronamic/wp-pronamic-pay-restrict-content-pro/commit/9bbc53d5b053fdad11d4b029b3633cb111a9f967))
- Only update active Restrict Content Pro membership if it matches the last payment. ([bf0c215](https://github.com/pronamic/wp-pronamic-pay-restrict-content-pro/commit/bf0c215e83020173e9a92604ead7043b7fdcd1db))
- Display Pronamic Pay payments in table view. ([32c54c4](https://github.com/pronamic/wp-pronamic-pay-restrict-content-pro/commit/32c54c4ce93bf5f6779442251c538379d8c3fc02))
- Connect last Pronamic payment to RCP payment and membership. ([9ec7fbc](https://github.com/pronamic/wp-pronamic-pay-restrict-content-pro/commit/9ec7fbc0bd0027ea38411f3e84a66b089835aa24))
- Redirect to account page if payment is not succesful. ([6d02863](https://github.com/pronamic/wp-pronamic-pay-restrict-content-pro/commit/6d028630a6107a650cbe260d21089a77c568ddda))
- Only store transaction ID in RCP payment if successful. ([5348592](https://github.com/pronamic/wp-pronamic-pay-restrict-content-pro/commit/5348592373b0b03256673a9b9e10b2099a0d2049))
- Added new StellarWP GitHub link. ([c3a1e40](https://github.com/pronamic/wp-pronamic-pay-restrict-content-pro/commit/c3a1e40e8026a85bc3b167af1d7aff6ba6bd93a9))
- Change links list notation. ([034587e](https://github.com/pronamic/wp-pronamic-pay-restrict-content-pro/commit/034587e88eaa067a5648098b4570eba4eec01510))
- Improve escaping in exception messages. ([d792791](https://github.com/pronamic/wp-pronamic-pay-restrict-content-pro/commit/d79279195ac8968833329055bf01311abfb6637f))
- phpcbf ([eccb6a8](https://github.com/pronamic/wp-pronamic-pay-restrict-content-pro/commit/eccb6a8c748279518a447c54fdfda0ce68168b5e))
- Updated package.json ([c012ee9](https://github.com/pronamic/wp-pronamic-pay-restrict-content-pro/commit/c012ee9ad77d6261d53f2e02d39a42e913f3897d))

Full set of changes: [`4.3.6...4.4.0`][4.4.0]

[4.4.0]: https://github.com/pronamic/wp-pronamic-pay-restrict-content-pro/compare/v4.3.6...v4.4.0

## [4.3.6] - 2023-07-18

### Commits

- Updated version documentation in Giropay gateway. ([c6ce14d](https://github.com/pronamic/wp-pronamic-pay-restrict-content-pro/commit/c6ce14dc0777f80801ef24aeb0abad4f8896ad56))
- Added EPS payment method. ([c6912de](https://github.com/pronamic/wp-pronamic-pay-restrict-content-pro/commit/c6912de7789d821c4a52a7d096d61b4ad6d36e5f))

Full set of changes: [`4.3.5...4.3.6`][4.3.6]

[4.3.6]: https://github.com/pronamic/wp-pronamic-pay-restrict-content-pro/compare/v4.3.5...v4.3.6

## [4.3.5] - 2023-07-12

### Commits

- Added Giropay payment method. ([fe192f0](https://github.com/pronamic/wp-pronamic-pay-restrict-content-pro/commit/fe192f0c39fd04bfbd15624cc5af56f56c78a48e))

Full set of changes: [`4.3.4...4.3.5`][4.3.5]

[4.3.5]: https://github.com/pronamic/wp-pronamic-pay-restrict-content-pro/compare/v4.3.4...v4.3.5

## [4.3.4] - 2023-06-01

### Commits

- Switch from `pronamic/wp-deployer` to `pronamic/pronamic-cli`. ([b2b76b3](https://github.com/pronamic/wp-pronamic-pay-restrict-content-pro/commit/b2b76b3124961d78824b7713847b3214359db779))

Full set of changes: [`4.3.3...4.3.4`][4.3.4]

[4.3.4]: https://github.com/pronamic/wp-pronamic-pay-restrict-content-pro/compare/v4.3.3...v4.3.4

## [4.3.3] - 2023-03-27

### Commits

- Set Composer type to WordPress plugin. ([9c4f989](https://github.com/pronamic/wp-pronamic-pay-restrict-content-pro/commit/9c4f989d876fa1da672768374630131dfb331ff2))
- Created .gitattributes ([ec810be](https://github.com/pronamic/wp-pronamic-pay-restrict-content-pro/commit/ec810beb8557e73173da914d8f3581850f86d51b))

Full set of changes: [`4.3.2...4.3.3`][4.3.3]

[4.3.3]: https://github.com/pronamic/wp-pronamic-pay-restrict-content-pro/compare/v4.3.2...v4.3.3

## [4.3.2] - 2023-02-07
### Changed

- Upgrade `2.1.6` now runs asynchronously.
Full set of changes: [`4.3.1...4.3.2`][4.3.2]

[4.3.2]: https://github.com/pronamic/wp-pronamic-pay-restrict-content-pro/compare/v4.3.1...v4.3.2

## [4.3.1] - 2023-01-31
### Composer

- Changed `php` from `>=8.0` to `>=7.4`.
Full set of changes: [`4.3.0...4.3.1`][4.3.1]

[4.3.1]: https://github.com/pronamic/wp-pronamic-pay-restrict-content-pro/compare/v4.3.0...v4.3.1

## [4.3.0] - 2022-12-23

### Commits

- Added "Requires Plugins" plugin. ([9c44f8b](https://github.com/pronamic/wp-pronamic-pay-restrict-content-pro/commit/9c44f8ba54d46bbd32b42d4ff9ac54014f4247ab))

### Composer

- Changed `php` from `>=5.6.20` to `>=8.0`.
- Changed `pronamic/wp-money` from `^2.0` to `v2.2.0`.
	Release notes: https://github.com/pronamic/wp-money/releases/tag/v4.2.1
- Changed `wp-pay/core` from `^4.4` to `v4.6.0`.
	Release notes: https://github.com/pronamic/wp-pay-core/releases/tag/v4.2.1
Full set of changes: [`4.2.1...4.3.0`][4.3.0]

[4.3.0]: https://github.com/pronamic/wp-pronamic-pay-restrict-content-pro/compare/v4.2.1...v4.3.0

## [4.2.1] - 2022-09-27
- Update to `wp-pay/core` version `^4.4`.

## [4.2.0] - 2022-09-26
- Updated for new payment methods and fields registration.

## [4.1.0] - 2022-04-11
- Transform expired Restrict Content Pro membership to Pronamic status `Completed`.
- Fix missing gateway registration key.
- Simplify gateway registration and supported features.
- Coding standards.

## [4.0.0] - 2022-01-10
### Changed
- Updated to https://github.com/pronamic/wp-pay-core/releases/tag/4.0.0.
- Simplified gateway registration and add Apple Pay gateway.

## [3.0.0] - 2021-08-05
- Updated to `pronamic/wp-pay-core`  version `3.0.0`.
- Updated to `pronamic/wp-money`  version `2.0.0`.
- Changed `TaxedMoney` to `Money`, no tax info.
- Switched to `pronamic/wp-coding-standards`.

## [2.3.2] - 2021-04-26
- Fixed incorrect amount when using registration fees.

## [2.3.1] - 2021-01-14
- Renew inactive membership on successful (retry) payment.
- Fix not using checkout label setting.
- Coding standards.

## [2.3.0] - 2020-11-09
- Changed setting the next payment date 1 day earlier, to prevent temporary membership expirations.
- No longer mark Pronamic Pay subscriptions as expired when a Restrict Content Pro membership expires.
- Added support for new subscription phases and periods.
- Added support for trials to credit card and direct debit methods.
- Added support for payment fees.

## [2.2.3] - 2020-07-23
- Fixed possible "Fatal error: Call to a member function `get_id()` on null".

## [2.2.2] - 2020-07-08
- Added support for subscription frequency.
- Fixed using existing subscription for membership.
- Fixed expiring membership if first payment expires but subscription is already active.

## [2.2.1] - 2020-04-03
- Improved CLI commands.
- Improved 2.1.6 upgrade.
- Set plugin integration name.

## [2.2.0] - 2020-03-19
- Extension extends from abstract plugin integration with dependency.

## [2.1.7] - 2020-02-03
- Fixed possible "Fatal error: Call to a member function `get_user_id()` on boolean" in updater.

## [2.1.6] - 2019-12-22
- Added support for new dependencies system.
- Added support for new upgrades system.
- Added upgrade script for payment and subscriptions source.
- Improved error handling with exceptions.
- Updated subscription source details.

## [2.1.5] - 2019-10-07
- Restrict Content Pro 3.0 is required.
- Renew membership during `pronamic_pay_new_payment` routine and update membership expiration date and status on cancelled/expired/failed payment status update.

## [2.1.4] - 2019-08-26
- Fixed support for Restrict Content Pro 3.0.
- Updated packages.

## [2.1.3] - 2018-01-24
- Use taxed money object for subscription amount.

## [2.1.2] - 2018-01-17
- Added support for subscription cancellation.
- Update member auto renewal setting for first payment too.
- Use Restrict Content Pro success page return URL only for successful payments.
- Prevent using direct debit recurring payment methods for non-expiring subscriptions.

## [2.1.1] - 2018-12-10
- Use correct initial amount.
- Fix duplicate renewal.

## [2.1.0] - 2018-09-12
- Complete rewrite of library.

## [2.0.2] - 2018-07-06
- Improved subscription upgrades.

## [2.0.1] - 2018-05-16
- Improved recurring payments support.

## [2.0.0] - 2018-05-14
- Switched to PHP namespaces.

## 1.0.0 - 2017-12-13
- First release.

[unreleased]: https://github.com/wp-pay-extensions/restrict-content-pro/compare/4.2.1...HEAD
[4.2.1]: https://github.com/pronamic/wp-pronamic-pay-restrict-content-pro/compare/4.2.0...4.2.1
[4.2.0]: https://github.com/pronamic/wp-pronamic-pay-restrict-content-pro/compare/4.1.0...4.2.0
[4.1.0]: https://github.com/pronamic/wp-pronamic-pay-restrict-content-pro/compare/4.0.0...4.1.0
[4.0.0]: https://github.com/wp-pay-extensions/restrict-content-pro/compare/3.0.0...4.0.0
[3.0.0]: https://github.com/wp-pay-extensions/restrict-content-pro/compare/2.3.2...3.0.0
[2.3.2]: https://github.com/wp-pay-extensions/restrict-content-pro/compare/2.3.1...2.3.2
[2.3.1]: https://github.com/wp-pay-extensions/restrict-content-pro/compare/2.3.0...2.3.1
[2.3.0]: https://github.com/wp-pay-extensions/restrict-content-pro/compare/2.2.3...2.3.0
[2.2.3]: https://github.com/wp-pay-extensions/restrict-content-pro/compare/2.2.2...2.2.3
[2.2.2]: https://github.com/wp-pay-extensions/restrict-content-pro/compare/2.2.1...2.2.2
[2.2.1]: https://github.com/wp-pay-extensions/restrict-content-pro/compare/2.2.0...2.2.1
[2.2.0]: https://github.com/wp-pay-extensions/restrict-content-pro/compare/2.1.7...2.2.0
[2.1.7]: https://github.com/wp-pay-extensions/restrict-content-pro/compare/2.1.6...2.1.7
[2.1.6]: https://github.com/wp-pay-extensions/restrict-content-pro/compare/2.1.5...2.1.6
[2.1.5]: https://github.com/wp-pay-extensions/restrict-content-pro/compare/2.1.4...2.1.5
[2.1.4]: https://github.com/wp-pay-extensions/restrict-content-pro/compare/2.1.3...2.1.4
[2.1.3]: https://github.com/wp-pay-extensions/restrict-content-pro/compare/2.1.2...2.1.3
[2.1.2]: https://github.com/wp-pay-extensions/restrict-content-pro/compare/2.1.1...2.1.2
[2.1.1]: https://github.com/wp-pay-extensions/restrict-content-pro/compare/2.1.0...2.1.1
[2.1.0]: https://github.com/wp-pay-extensions/restrict-content-pro/compare/2.0.1...2.1.0
[2.0.2]: https://github.com/wp-pay-extensions/restrict-content-pro/compare/2.0.1...2.0.2
[2.0.1]: https://github.com/wp-pay-extensions/restrict-content-pro/compare/2.0.0...2.0.1
[2.0.0]: https://github.com/wp-pay-extensions/restrict-content-pro/compare/1.0.0...2.0.0
