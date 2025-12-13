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
use FAIR\DID\PLC\PlcOperation;
use FAIR\DID\Keys\EcKey;
use FAIR\DID\Keys\KeyFactory;

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

echo "::group::DID Service Update Debug Information\n";
echo "DID: {$did}\n";
echo "Metadata URL: {$metadataUrl}\n";

try {
    // Get current DID document
    echo "\nFetching current DID document...\n";
    $currentDoc = $client->resolve_did($did);
    echo "Current DID Document: " . json_encode($currentDoc, JSON_PRETTY_PRINT) . "\n";

    // Get last operation
    echo "\nFetching last operation...\n";
    $lastOp = $client->get_last_operation($did);
    if (null === $lastOp) {
        throw new \RuntimeException("Could not retrieve last operation for DID: {$did}");
    }
    echo "Last operation CID: " . ($lastOp['cid'] ?? 'null') . "\n";

    // Decode existing rotation keys
    $rotationKeysArray = [];
    $rotationKeyData = $currentDoc['rotationKeys'] ?? [$rotationPublic];
    echo "Raw rotation keys data: " . json_encode($rotationKeyData, JSON_PRETTY_PRINT) . "\n";
    foreach ($rotationKeyData as $keyStr) {
        try {
            $rotationKeysArray[] = KeyFactory::decode_did_key($keyStr);
        } catch (\Exception $e) {
            echo "Warning: Could not decode rotation key '{$keyStr}': " . $e->getMessage() . "\n";
            // If decoding fails, just use the public key we have
            $rotationKeysArray[] = KeyFactory::decode_did_key($rotationPublic);
            break;
        }
    }
    echo "Rotation keys count: " . count($rotationKeysArray) . "\n";

    // Decode existing verification methods
    $verificationMethods = [];
    $methodsData = $currentDoc['verificationMethod'] ?? [];  // Note: it's 'verificationMethod' not 'verificationMethods'
    echo "Raw verification methods data: " . json_encode($methodsData, JSON_PRETTY_PRINT) . "\n";
    foreach ($methodsData as $method) {
        try {
            $methodId = $method['id'] ?? '';
            $publicKeyMultibase = $method['publicKeyMultibase'] ?? '';
            if (!empty($publicKeyMultibase)) {
                $verificationMethods[$methodId] = KeyFactory::decode_did_key($publicKeyMultibase);
            }
        } catch (\Exception $e) {
            echo "Warning: Could not decode verification method: " . $e->getMessage() . "\n";
        }
    }
    echo "Verification methods count: " . count($verificationMethods) . "\n";

    // Get existing values
    $alsoKnownAs = $currentDoc['alsoKnownAs'] ?? [];
    $services = $currentDoc['services'] ?? [];

    // Update services with FAIR endpoint
    $services['fairpm_repo'] = [
        'type' => 'FairPackageManagementRepo',
        'endpoint' => $metadataUrl,
    ];

    echo "\nBuilding update operation...\n";
    echo "Services to include: " . json_encode($services, JSON_PRETTY_PRINT) . "\n";

    // Build update operation
    $operation = new PlcOperation(
        type: 'plc_operation',
        rotation_keys: $rotationKeysArray,
        verification_methods: $verificationMethods,
        also_known_as: $alsoKnownAs,
        services: $services,
        prev: $lastOp['cid'] ?? null,
    );

    // Sign and submit
    echo "\nSigning operation...\n";
    $signedOp = $operation->sign($rotationKey);
    $operationArray = (array) $signedOp->jsonSerialize();
    echo "Signed Operation: " . json_encode($operationArray, JSON_PRETTY_PRINT) . "\n";

    echo "\nSubmitting update to PLC directory...\n";
    $response = $client->update_did($did, $operationArray);

    echo "::endgroup::\n";
    echo "::notice::✅ DID updated with FAIR service endpoint: {$metadataUrl}\n";
    if (!empty($response)) {
        echo "::notice::PLC Response: " . json_encode($response, JSON_PRETTY_PRINT) . "\n";
    }

    // Verify the update by fetching the DID document again
    echo "\n::group::Verifying DID Update\n";
    $updatedDoc = $client->resolve_did($did);
    echo "Updated DID Document: " . json_encode($updatedDoc, JSON_PRETTY_PRINT) . "\n";

    if (isset($updatedDoc['service']) && !empty($updatedDoc['service'])) {
        echo "::endgroup::\n";
        echo "::notice::✅ Services array updated successfully\n";
    } else {
        echo "::endgroup::\n";
        echo "::warning::⚠️ Services array is empty after update\n";
    }
} catch (Exception $e) {
    echo "::endgroup::\n";
    echo "::error::❌ Could not update DID service: " . $e->getMessage() . "\n";
    echo "::error::Stack trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}
