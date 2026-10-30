# Changelog

## 0.2.1

- Installs on Magento 2.4.7+ and PHP 8.2–8.5.
- CI tests PHP 8.2–8.5; CI installs no longer notify Packagist.
- Composer keywords, homepage and support links.
- README: experimental notice, setup screenshot, compact requirements.

## 0.2.0

- Removed the Google request normalizer — `admin-scim` already coerces a stringified `active`;
  the module no longer touches other IdPs' requests.
- Setup panel corrected to what Google documents: Auto-provisioning precondition, users only
  (no group push → Admin SCIM Default Role), `admin-sso-google` fallback; translatable
  strings (`i18n/en_US.csv`).
- Declared `magento/module-backend` and `magento/module-config` dependencies.
- Requires `dmlab/module-admin-scim ^0.2.0`.
