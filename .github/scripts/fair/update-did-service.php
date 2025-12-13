#!/usr/bin/env php
<?php
/**
 * FAIR DID Service Update Script
 *
 * Updates the DID with the FAIR service endpoint.
 *
 * Required environment variables:
 * - DID
 * - ROTATION_PRIVATE
 * - ROTATION_PUBLIC
 * - VERIFICATION_PUBLIC
 * - REPO_URL
 *
 * @package MinimalAdmin
 */

declare(strict_types=1);

// Autoload path - did-manager is cloned to /tmp/did-manager in GitHub Actions
$autoloadPath = '/tmp/did-manager/vendor/autoload.php';

if (!file_exists($autoloadPath)) {
    echo "::error::Autoloader not found at {$autoloadPath}\n";
    exit(1);
}

require_once $autoloadPath;

use FAIR\DID\Crypto\DidCodec;
use FAIR\DID\PLC\PlcClient;
use FAIR\DID\Keys\EcKey;

// Get environment variables
$did = getenv('DID');
$rotationPrivate = getenv('ROTATION_PRIVATE');
$rotationPublic = getenv('ROTATION_PUBLIC');
$verificationPublic = getenv('VERIFICATION_PUBLIC');
$metadataUrl = getenv('METADATA_URL');

// Validate required inputs
if (empty($did)) {
    echo "::error::DID is required\n";
    exit(1);
}

if (empty($rotationPrivate) || empty($rotationPublic)) {
    echo "::error::Rotation keys are required\n";
    exit(1);
}

if (empty($metadataUrl)) {
    echo "::error::Metadata URL is required\n";
    exit(1);
}

// Reconstruct rotation key from private key
$rotationKey = EcKey::from_private($rotationPrivate);
$client = new PlcClient();

// Create update operation to add FAIR service
$service = [
    [
        'id' => '#fairpm_repo',
        'type' => 'FairPackageManagementRepo',
        'serviceEndpoint' => $metadataUrl,
    ],
];

try {
    // Get current DID document
    $currentDoc = $client->resolve_did($did);

    // Create update operation
    $updateOp = DidCodec::create_update_operation(
        $did,
        $rotationKey,
        $verificationPublic,
        $service,
        $currentDoc
    );

    // Sign and submit
    $signedOp = DidCodec::sign_plc_operation($updateOp, $rotationKey);
    $operationArray = (array) $signedOp->jsonSerialize();
    $response = $client->update_did($did, $operationArray);

    echo "::notice::DID updated with FAIR service endpoint: {$metadataUrl}\n";
    if (!empty($response)) {
        echo "::notice::Update Response: " . json_encode($response) . "\n";
    }
} catch (Exception $e) {
    echo "::warning::Could not update DID service: " . $e->getMessage() . "\n";
}
