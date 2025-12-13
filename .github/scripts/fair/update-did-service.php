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
 * - METADATA_URL
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

use FAIR\DID\PLC\PlcClient;
use FAIR\DID\PLC\PlcOperation;
use FAIR\DID\Keys\EcKey;
use FAIR\DID\Keys\KeyFactory;

// Get environment variables
$did = getenv('DID');
$rotationPrivate = getenv('ROTATION_PRIVATE');
$metadataUrl = getenv('METADATA_URL');

// Validate required inputs
if (empty($did)) {
    echo "::error::DID is required\n";
    exit(1);
}

if (empty($rotationPrivate)) {
    echo "::error::Rotation private key is required\n";
    exit(1);
}

if (empty($metadataUrl)) {
    echo "::error::Metadata URL is required\n";
    exit(1);
}

// Reconstruct rotation key from private key
echo "::notice::Reconstructing rotation key from private key...\n";
$rotationKey = EcKey::from_private($rotationPrivate);
echo "::notice::Rotation key reconstructed successfully\n";

echo "::notice::Initializing PLC client...\n";
$client = new PlcClient();
echo "::notice::PLC client initialized\n";

try {
    // Get current DID document and last operation
    echo "::notice::Fetching current DID document for: {$did}\n";
    $currentDoc = $client->resolve_did($did);
    echo "::notice::DID document retrieved successfully\n";

    echo "::notice::Fetching last operation...\n";
    $lastOp = $client->get_last_operation($did);

    if (null === $lastOp) {
        throw new \RuntimeException("Could not retrieve last operation for DID: {$did}");
    }
    echo "::notice::Last operation retrieved - CID: " . ($lastOp['cid'] ?? 'null') . "\n";

    echo "::group::Current DID Document\n";
    echo json_encode($currentDoc, JSON_PRETTY_PRINT) . "\n";
    echo "::endgroup::\n";

    // Decode existing verification methods
    echo "::notice::Decoding existing verification methods...\n";
    $verificationMethods = [];
    $methodsData = $currentDoc['verificationMethod'] ?? [];
    echo "::notice::Found " . count($methodsData) . " verification methods in current document\n";

    foreach ($methodsData as $method) {
        $methodId = $method['id'] ?? '';
        $publicKeyMultibase = $method['publicKeyMultibase'] ?? '';
        if (!empty($publicKeyMultibase)) {
            echo "::notice::Decoding verification method: {$methodId}\n";
            $verificationMethods[$methodId] = KeyFactory::decode_did_key($publicKeyMultibase);
        }
    }
    echo "::notice::Successfully decoded " . count($verificationMethods) . " verification methods\n";

    // Get existing values
    echo "::notice::Extracting existing DID document values...\n";
    $alsoKnownAs = $currentDoc['alsoKnownAs'] ?? [];
    $services = $currentDoc['services'] ?? [];
    echo "::notice::Found " . count($alsoKnownAs) . " handles and " . count($services) . " existing services\n";

    // Update services with FAIR endpoint
    $services[] = [
		'id' => "#fairpm_repo",
        'type' => 'FairPackageManagementRepo',
        'endpoint' => $metadataUrl,
    ];

    echo "::group::Update Details\n";
    echo "Services to update: " . json_encode($services, JSON_PRETTY_PRINT) . "\n";
    echo "Previous operation CID: " . ($lastOp['cid'] ?? 'null') . "\n";
    echo "::endgroup::\n";

    // Build update operation
    $operation = new PlcOperation(
        type: 'plc_operation',
        rotation_keys: [$rotationKey],
        verification_methods: $verificationMethods,
        also_known_as: $alsoKnownAs,
        services: $services,
        prev: $lastOp['cid'] ?? null,
    );

    // Sign the operation
    echo "::notice::Signing operation...\n";
    $signedOp = $operation->sign($rotationKey);
    $operationArray = (array) $signedOp->jsonSerialize();

    echo "::group::Signed Operation\n";
    echo json_encode($operationArray, JSON_PRETTY_PRINT) . "\n";
    echo "::endgroup::\n";

    // Submit update to PLC directory
    echo "::notice::Submitting update to PLC directory...\n";
    $client->update_did($did, $operationArray);

    echo "::notice::DID updated with FAIR service endpoint: {$metadataUrl}\n";

    // Verify the update
    echo "::notice::Verifying update by fetching DID document again...\n";
    $updatedDoc = $client->resolve_did($did);
    echo "::notice::Updated DID document retrieved\n";

    echo "::group::Updated DID Document\n";
    echo json_encode($updatedDoc, JSON_PRETTY_PRINT) . "\n";
    echo "::endgroup::\n";

    $serviceCount = isset($updatedDoc['service']) ? count($updatedDoc['service']) : 0;
    echo "::notice::Services in updated document: {$serviceCount}\n";

    if (isset($updatedDoc['service']) && !empty($updatedDoc['service'])) {
        echo "::notice::Services array updated successfully\n";
        foreach ($updatedDoc['service'] as $service) {
            $serviceId = $service['id'] ?? 'unknown';
            $serviceType = $service['type'] ?? 'unknown';
            echo "::notice::Service found - ID: {$serviceId}, Type: {$serviceType}\n";
        }
    } else {
        echo "::warning::Services array is empty after update\n";
    }
} catch (Exception $e) {
    echo "::error::Could not update DID service: " . $e->getMessage() . "\n";
    exit(1);
}
