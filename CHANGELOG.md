# Change Log

All notable changes to this project will be documented in this file.

This projects adheres to [Semantic Versioning](http://semver.org/) and [Keep a CHANGELOG](http://keepachangelog.com/).

## [Unreleased][unreleased]
-

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
- Use taxed money object for subscripption amount.

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

[unreleased]: https://github.com/wp-pay-extensions/restrict-content-pro/compare/2.2.3...HEAD
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
