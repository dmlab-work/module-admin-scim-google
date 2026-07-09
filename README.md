# MageDevGroup_AdminScimGoogle

> Google Workspace SCIM provisioning for Magento 2 admin users.

![License](https://img.shields.io/badge/license-OSL--3.0-green) ![Magento](https://img.shields.io/badge/Magento-2.4-orange) ![PHP](https://img.shields.io/badge/PHP-8.3--8.5-blue) ![Version](https://img.shields.io/badge/version-0.0.1-lightgrey)

A thin Google Workspace (Cloud Identity) provider plugin for the [`admin-scim`](../module-admin-scim) SCIM 2.0 server. It normalizes Google's SCIM specifics and documents the Google-side auto-provisioning setup. Google is near RFC-standard, so the module is small: a mostly pass-through normalizer plus an admin setup surface.

## Install

```bash
composer require magedevgroup/module-admin-scim-google
bin/magento module:enable MageDevGroup_AdminScimGoogle
bin/magento setup:upgrade
```

The single `require` pulls `magedevgroup/module-admin-scim` — the whole provisioning chain installs at once.

## Set up Google Workspace auto-provisioning

1. In Magento, open **Stores → Configuration → MageDevGroup → Admin SCIM**, enable Admin SCIM, and set a **Bearer Token**.
2. Read the endpoint from **Stores → Configuration → MageDevGroup → Admin SCIM → Google Workspace Setup**. It is this store's SCIM base URL, e.g. `https://your-host/admin-scim/v2`.
3. In the Google Admin console, open **Apps → Web and mobile apps → your SCIM app → Auto-provisioning** and enter:

   | Setting | Value |
   |---|---|
   | Endpoint URL | the URL from step 2 |
   | Access token | the Bearer Token from step 1 (sent as `Authorization: Bearer <token>`) |
   | Provisioning scope | provision (create/update), deactivate, group membership |

4. Enable auto-provisioning and assign users/groups.

## Supported provisioning actions

- **Create** — Google pushes a new user → admin_user created.
- **Update** — profile changes → admin_user updated.
- **Deactivate / reactivate** — sent by Google as `active = false` / `active = true`.
- **Group push** — group membership → role mapping (standard SCIM, handled by `admin-scim`).

## How it works

Google pushes SCIM 2.0 requests to the `admin-scim` endpoint. This plugin registers `GoogleRequestNormalizer` into `admin-scim`'s `RequestNormalizerInterface` seam (di-merged under the `google` key) and coerces Google's one deviation — an `active` toggle sometimes serialized as a stringified boolean (`"true"`/`"false"`) — into a native boolean, on the top-level body (POST/PUT) and inside PATCH operations. Groups and everything else pass through unchanged; the actual admin-user provisioning and role mapping happen in `admin-scim`.

## Requirements

- Magento **2.4.x**
- PHP **8.3 – 8.5**
- `magedevgroup/module-admin-scim` (installed automatically)

## License

[OSL-3.0](LICENSE) © MageDevGroup. Commercial licensing and support: <https://magedevgroup.com>.
