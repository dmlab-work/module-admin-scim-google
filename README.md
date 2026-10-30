# DmLab_AdminScimGoogle

> Google Workspace SCIM provisioning for Magento 2 admin users.

[![CI](https://github.com/dmlab-work/module-admin-scim-google/actions/workflows/ci.yml/badge.svg)](https://github.com/dmlab-work/module-admin-scim-google/actions/workflows/ci.yml) [![codecov](https://codecov.io/gh/dmlab-work/module-admin-scim-google/graph/badge.svg)](https://codecov.io/gh/dmlab-work/module-admin-scim-google) [![Packagist Version](https://img.shields.io/packagist/v/dmlab/module-admin-scim-google)](https://packagist.org/packages/dmlab/module-admin-scim-google) [![Downloads](https://img.shields.io/packagist/dt/dmlab/module-admin-scim-google)](https://packagist.org/packages/dmlab/module-admin-scim-google) [![PHP](https://img.shields.io/packagist/dependency-v/dmlab/module-admin-scim-google/php)](https://packagist.org/packages/dmlab/module-admin-scim-google) [![License](https://img.shields.io/packagist/l/dmlab/module-admin-scim-google)](LICENSE) ![Magento](https://img.shields.io/badge/Magento-2.4-orange)

> **Status: experimental.** Works only when Google shows an **Auto-provisioning** section for your app (catalog apps). No such section? Use [admin-sso-google](https://github.com/dmlab-work/module-admin-sso-google) instead (JIT create/update at login).

Staff who join or leave your Google Workspace no longer need their Magento admin accounts created and removed by hand.
Google creates, updates and deactivates Magento admin users automatically (users only, no groups).
Install this plugin and the SCIM server comes with it.

![Google Workspace auto-provisioning setup in Magento admin: endpoint URL and access token](.github/images/config.png)

## Install

```bash
composer require dmlab/module-admin-scim-google
bin/magento module:enable DmLab_AdminScimGoogle
bin/magento setup:upgrade
```

The single `require` pulls `dmlab/module-admin-scim` — the whole provisioning chain installs at once.

## Set up Google Workspace auto-provisioning

**Precondition:** in the Google Admin console, **Apps → Web and mobile apps → your app** must show an **Auto-provisioning** section with **Endpoint URL** and **Access token** fields. Google documents this for catalog apps. If the fields are absent, Google cannot push SCIM to Magento — use [admin-sso-google](https://github.com/dmlab-work/module-admin-sso-google) (JIT create/update at login) instead.

1. In Magento, open **Stores → Configuration → DMLab → Admin SCIM**, enable Admin SCIM, and set a **Bearer Token**.
2. Read the endpoint from **Stores → Configuration → DMLab → Admin SCIM → Google Workspace Setup**. It is this store's SCIM base URL, e.g. `https://your-host/admin-scim/v2`.
3. In the app's **Auto-provisioning** section, enter:

   | Setting | Value |
   |---|---|
   | Endpoint URL | the URL from step 2 |
   | Access token | the Bearer Token from step 1 (sent as `Authorization: Bearer <token>`) |

4. Enable auto-provisioning and assign **users** (groups are not pushed).

## Supported provisioning actions

Status: per Google's documentation; not yet verified against a live tenant.

- **Create** — a newly assigned user → admin_user created.
- **Update** — profile changes → admin_user updated.
- **Suspend / delete** — a suspended or deleted Google user → admin_user deactivated or removed.
- Changes sync every few hours.
- **No group push.** The Group → Role Map does not apply: provisioned admins get the Admin SCIM **Default Role Id** — set it. If it is empty they get no role, and a role assigned manually is reset on the next sync.
- **Provider** selector (Admin SCIM → Provider): leave at "— None —". If exactly one other SCIM provider plugin is installed, its normalizer still runs under "— None —".

## Requirements

Magento 2.4.7+ · PHP 8.2–8.5 · `dmlab/module-admin-scim` ^0.2.0 (installed automatically)

Pairs with [admin-scim](https://github.com/dmlab-work/module-admin-scim) (the SCIM 2.0 server) and [admin-sso-google](https://github.com/dmlab-work/module-admin-sso-google) (Google login for the same admins).

## Architecture

A thin Google Workspace (Cloud Identity) provider plugin for the [admin-scim](https://github.com/dmlab-work/module-admin-scim) SCIM 2.0 server. Google pushes SCIM 2.0 requests straight to the `admin-scim` endpoint, which does all provisioning. This plugin adds only the setup panel; it registers no request normalizer.

## License

[OSL-3.0](LICENSE) © DMLab. Commercial licensing and support: <https://dmlab.work>.
