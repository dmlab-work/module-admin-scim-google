# Changelog

## 0.2.0

- Removed the Google request normalizer — `admin-scim` already coerces a stringified `active`;
  the module no longer touches other IdPs' requests.
- Setup panel corrected to what Google documents: Auto-provisioning precondition, users only
  (no group push → Admin SCIM Default Role), `admin-sso-google` fallback; translatable
  strings (`i18n/en_US.csv`).
- Declared `magento/module-backend` and `magento/module-config` dependencies.
- Requires `dmlab/module-admin-scim ^0.2.0`.
