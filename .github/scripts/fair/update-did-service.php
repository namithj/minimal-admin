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
$rotationKey = EcKey::from_private($rotationPrivate);
$client = new PlcClient();

try {
    // Get current DID document and last operation
    $currentDoc = $client->resolve_did($did);
    $lastOp = $client->get_last_operation($did);

    if (null === $lastOp) {
        throw new \RuntimeException("Could not retrieve last operation for DID: {$did}");
    }

    echo "::group::Current DID Document (Before Update)\n";
    echo json_encode($currentDoc, JSON_PRETTY_PRINT) . "\n";
    echo "::endgroup::\n";

    // Decode existing verification methods
    $verificationMethods = [];
    $methodsData = $currentDoc['verificationMethod'] ?? [];
    foreach ($methodsData as $method) {
        $methodId = $method['id'] ?? '';
        $publicKeyMultibase = $method['publicKeyMultibase'] ?? '';
        if (!empty($publicKeyMultibase)) {
            $verificationMethods[$methodId] = KeyFactory::decode_did_key($publicKeyMultibase);
        }
    }

    // Get existing values
    $alsoKnownAs = $currentDoc['alsoKnownAs'] ?? [];
    $services = $currentDoc['services'] ?? [];

    echo "::group::Services Configuration\n";
    echo "Existing services: " . json_encode($services, JSON_PRETTY_PRINT) . "\n";
    
    // Update services with FAIR endpoint
    $services['fairpm_repo'] = [
        'type' => 'FairPackageManagementRepo',
        'endpoint' => $metadataUrl,
    ];
    
    echo "Updated services: " . json_encode($services, JSON_PRETTY_PRINT) . "\n";
    echo "::endgroup::\n";

    // Build and sign update operation
    $operation = new PlcOperation(
        type: 'plc_operation',
    echo "::group::Signed Operation\n";
    echo json_encode($operationArray, JSON_PRETTY_PRINT) . "\n";
    echo "::endgroup::\n";

    // Submit update to PLC directory
    $client->update_did($did, $operationArray);

    echo "::notice::DID updated with FAIR service endpoint: {$metadataUrl}\n";

    // Verify the update
    $updatedDoc = $client->resolve_did($did);
    
    echo "::group::Updated DID Document (After Update)\n";
    echo json_encode($updatedDoc, JSON_PRETTY_PRINT) . "\n";
    echo "::endgroup::\n";
    
    $signedOp = $operation->sign($rotationKey);
    $operationArray = (array) $signedOp->jsonSerialize();

    // Submit update to PLC directory
    $client->update_did($did, $operationArray);

    echo "::notice::DID updated with FAIR service endpoint: {$metadataUrl}\n";

    // Verify the update
    $updatedDoc = $client->resolve_did($did);
    if (isset($updatedDoc['service']) && !empty($updatedDoc['service'])) {
        echo "::notice::Services array updated successfully\n";
    } else {
        echo "::warning::Services array is empty after update\n";
    }
} catch (Exception $e) {
    echo "::error::Could not update DID service: " . $e->getMessage() . "\n";
    exit(1);
}
