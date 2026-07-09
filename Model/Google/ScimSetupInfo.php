<?php
/**
 * Copyright © MageDevGroup. All rights reserved.
 */
declare(strict_types=1);

namespace MageDevGroup\AdminScimGoogle\Model\Google;

use Magento\Framework\Escaper;
use MageDevGroup\AdminScim\Model\Discovery\EndpointUrlBuilder;

/**
 * Renders the admin setup surface for wiring Google Workspace (Cloud Identity)
 * auto-provisioning to this store.
 *
 * Google's auto-provisioning config (Admin console &rarr; Apps &rarr; Web and
 * mobile apps &rarr; the SCIM app &rarr; Auto-provisioning) has two inputs the
 * admin can't guess: the **SCIM base URL** (read from the core's
 * {@see EndpointUrlBuilder}) and the **access token** (this store's admin-scim
 * bearer token). The rest is fixed Google-side guidance (provisioning scope,
 * deactivation semantics). Instantiable without Magento's block stack, so it's
 * unit-testable on its own.
 *
 * Google's SCIM client is broadly RFC-7644-compliant — deactivation is sent as
 * `active = false`, groups push as standard SCIM — so the guidance states the
 * behaviour rather than describing any Google-side workaround.
 */
class ScimSetupInfo
{
    /**
     * @param EndpointUrlBuilder $endpointUrlBuilder
     * @param Escaper $escaper
     */
    public function __construct(
        private readonly EndpointUrlBuilder $endpointUrlBuilder,
        private readonly Escaper $escaper
    ) {
    }

    /**
     * The SCIM base URL to paste into Google's Endpoint URL field (e.g. `https://host/admin-scim/v2`).
     */
    public function getEndpointUrl(): string
    {
        return $this->endpointUrlBuilder->baseUrl();
    }

    /**
     * Info-panel HTML for the admin config field: the endpoint plus Google auto-provisioning steps.
     */
    public function getHtml(): string
    {
        $endpoint = $this->escaper->escapeHtml($this->getEndpointUrl());

        return <<<HTML
<div class="magedevgroup-admin-scim-google-setup">
    <p>In the Google Admin console (Apps &rarr; Web and mobile apps &rarr; your SCIM
        app &rarr; Auto-provisioning), use these settings:</p>
    <ul>
        <li><strong>Endpoint URL:</strong> <code>{$endpoint}</code></li>
        <li><strong>Access token:</strong> the admin-scim bearer token (see below),
            sent as <code>Authorization: Bearer &lt;token&gt;</code>.</li>
        <li><strong>Provisioning scope:</strong> provision (create/update),
            deactivate and group membership (deactivation is sent as
            <code>active = false</code>).</li>
    </ul>
    <p>Google's SCIM client is near-standard, so no Google-side workaround is
        needed &mdash; users and groups push in RFC-7644 shape.</p>
    <p>Use the token configured under
        <em>Stores &rarr; Configuration &rarr; MageDevGroup &rarr; Admin SCIM &rarr; Bearer Token</em>,
        and enable Admin SCIM there first.</p>
</div>
HTML;
    }
}
