<?php
/**
 * Copyright © DMLab. All rights reserved.
 * Licensed under the Open Software License version 3.0 (OSL-3.0). See LICENSE.
 */
declare(strict_types=1);

namespace DmLab\AdminScimGoogle\Model\Google;

use Magento\Framework\Escaper;
use Magento\Framework\Phrase;
use DmLab\AdminScim\Model\Discovery\EndpointUrlBuilder;

/**
 * Renders the admin setup surface for wiring Google Workspace auto-provisioning to this store.
 *
 * States only what Google documents: user provisioning (no groups) for apps whose Admin console
 * page shows Auto-provisioning with Endpoint URL / Access token fields. Instantiable without
 * Magento's block stack, so it's unit-testable on its own.
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
     * Info-panel HTML for the admin config field: the endpoint plus Google auto-provisioning guidance.
     */
    public function getHtml(): string
    {
        $precondition = $this->escape(
            __('In the Google Admin console, open Apps → Web and mobile apps → your app.'),
            __('The app must show an Auto-provisioning section with Endpoint URL and Access token fields.'),
            __('Google documents this section for catalog apps.'),
            __('If it is absent, Google cannot push SCIM to this store: use admin-sso-google (JIT login) instead.')
        );
        $intro = $this->escape(__('In the Auto-provisioning section, enter:'));
        $endpoint = $this->escape(__('Endpoint URL: %1', $this->getEndpointUrl()));
        $token = $this->escape(
            __('Access token: the Bearer Token from Stores → Configuration → DMLab → Admin SCIM.'),
            __('Enable Admin SCIM there first.')
        );
        $roles = $this->escape(
            __('Google pushes users only, not groups, so the Group → Role Map does not apply.'),
            __('Provisioned admins get the Admin SCIM Default Role Id; set it.'),
            __('If it is empty they get no role, and a role assigned manually is reset on the next sync.')
        );
        $provider = $this->escape(
            __('Admin SCIM → Provider: leave it at "— None —" (this plugin registers no normalizer).'),
            __('If exactly one other SCIM provider plugin is installed, its normalizer still runs under "— None —".')
        );
        $sync = $this->escape(__('Google syncs changes every few hours.'));

        return <<<HTML
<div class="dmlab-admin-scim-google-setup">
    <p>{$precondition}</p>
    <p>{$intro}</p>
    <ul>
        <li>{$endpoint}</li>
        <li>{$token}</li>
    </ul>
    <p>{$roles}</p>
    <p>{$provider}</p>
    <p>{$sync}</p>
</div>
HTML;
    }

    /**
     * Render translated phrases as HTML-escaped text, joined by spaces.
     *
     * @param Phrase $phrases
     * @return string
     */
    private function escape(Phrase ...$phrases): string
    {
        return implode(' ', array_map(
            fn (Phrase $phrase): string => (string)$this->escaper->escapeHtml((string)$phrase),
            $phrases
        ));
    }
}
