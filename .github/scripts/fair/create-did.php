#!/usr/bin/env php
<?php
/**
 * FAIR DID Creation Script
 *
 * Creates or uses an existing PLC DID for the package.
 *
 * Required environment variables:
 * - ROTATION_PRIVATE
 * - ROTATION_PUBLIC
 * - VERIFICATION_PRIVATE
 * - VERIFICATION_PUBLIC
 * - EXISTING_DID (optional)
 * - DID_EXISTS
 * - REPO_URL
 * - GITHUB_OUTPUT (for GitHub Actions output)
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
use FAIR\DID\Keys\EdDsaKey;

/**
 * Write output to GitHub Actions output file.
 *
 * @param string $name  Output name.
 * @param string $value Output value.
 */
function write_output(string $name, string $value): void {
    $outputFile = getenv('GITHUB_OUTPUT');
    if (!$outputFile) {
        return;
    }
    file_put_contents($outputFile, "{$name}={$value}\n", FILE_APPEND);
}

// Get environment variables
$rotationPrivate = getenv('ROTATION_PRIVATE');
$rotationPublic = getenv('ROTATION_PUBLIC');
$verificationPrivate = getenv('VERIFICATION_PRIVATE');
$verificationPublic = getenv('VERIFICATION_PUBLIC');
$existingDid = getenv('EXISTING_DID');
$didExists = getenv('DID_EXISTS') === 'true';
$repoUrl = getenv('REPO_URL');

// Validate required inputs
if (empty($rotationPrivate) || empty($rotationPublic)) {
    echo "::error::Rotation keys are required\n";
    exit(1);
}

if (empty($verificationPrivate) || empty($verificationPublic)) {
    echo "::error::Verification keys are required\n";
    exit(1);
}

// Reconstruct keys from stored private keys
$rotationKey = EcKey::from_private($rotationPrivate);
$verificationKey = EdDsaKey::from_private($verificationPrivate);

if ($didExists && !empty($existingDid)) {
    echo "::notice::Using existing DID: {$existingDid}\n";
    write_output('did', $existingDid);
    write_output('created', 'false');
} else {
    echo "::notice::Creating new PLC DID...\n";

    // Create handle from repository name
    $handle = basename($repoUrl);

    // Create PLC operation for genesis
    $operation = DidCodec::create_plc_operation(
        $rotationKey,
        $verificationKey,
        $handle
    );

    // Sign the operation
    $signedOperation = DidCodec::sign_plc_operation($operation, $rotationKey);

    // Generate DID from signed operation
    $did = DidCodec::generate_plc_did($signedOperation);

    echo "::notice::Generated DID: {$did}\n";
    echo "::warning::Please add FAIR_DID secret with value: {$did}\n";

    // Submit to PLC directory
    $client = new PlcClient();
    try {
        $client->submit_operation($did, $signedOperation);
        echo "::notice::DID submitted to PLC directory successfully\n";
    } catch (Exception $e) {
        echo "::warning::Could not submit to PLC directory: " . $e->getMessage() . "\n";
        echo "::notice::DID can still be used locally\n";
    }

    write_output('did', $did);
    write_output('created', 'true');
}
