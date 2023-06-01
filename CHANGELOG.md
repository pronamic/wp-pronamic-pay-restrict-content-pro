# Change Log

All notable changes to this project will be documented in this file.

This projects adheres to [Semantic Versioning](http://semver.org/) and [Keep a CHANGELOG](http://keepachangelog.com/).

## [Unreleased][unreleased]
-

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
