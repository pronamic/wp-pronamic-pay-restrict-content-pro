# WordPress Pay Extension: Restrict Content Pro

**Restrict Content Pro driver for the WordPress payment processing library.**

## Restrict Content Pro

https://gitlab.com/pronamic-plugins/restrict-content-pro

To download the latest version of Restrict Content Pro the plugin updater API can be used.
You have to replace `LICENSE` and `URL` in the following command. If you need a beta
release you can set `beta` to `1`.

```
curl --request POST https://restrictcontentpro.com/ \
    --data "edd_action=get_version&license=LICENSE&item_name=restrict-content-pro&item_id=479&version=1.0.0&slug=restrict-content-pro&author=Restrict Content Pro Team&url=URL&beta=0" \
    | jq '.package'
```

### Edit Membership

In Restrict Content Pro version 3 or higher it is possible to edit memberships.
Changes to a Restrict Content Pro membership can also influence the connected
Pronamic Pay subscription:

![Restrict Content Pro edit membership](documentation/restrict-content-pro-3-edit-membership.png)

#### Edit "Membership Level"

?

#### Edit "Date Created"

Editing the Restrict Content Pro membership "Date Created" value will not affect
the connected Pronamic Pay subscription. The Pronamic Pay subscription date will
not be updated.

#### Edit "Expiration Date"

?

#### Edit "Auto Renew"

?

## Test

*	Not logged in new membership.
*	Logged in new membership.
*	Logged in upgrade membership.
*	Cancel membership.
*	Discount code.
*	Lifetime membership.
*	Free trial.
*	Fee.

## Links

*	[Restrict Content Pro](https://restrictcontentpro.com/)
*	[GitHub Restrict Content Pro](https://github.com/restrictcontentpro/restrict-content-pro)
