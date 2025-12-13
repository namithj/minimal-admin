#!/usr/bin/env php
<?php
/**
 * FAIR Artifact Signing Script
 *
 * Signs a package artifact using the verification key.
 *
 * Required environment variables:
 * - VERIFICATION_PRIVATE
 * - VERIFICATION_PUBLIC
 * - ARTIFACT_PATH
 * - GITHUB_OUTPUT (for GitHub Actions output)
 *
 * @package MinimalAdmin
 */

declare(strict_types=1);

// Autoload path is passed as first argument or defaults to /tmp/did-manager
$autoloadPath = $argv[1] ?? '/tmp/did-manager/vendor/autoload.php';

if (!file_exists($autoloadPath)) {
    echo "::error::Autoloader not found at {$autoloadPath}\n";
    exit(1);
}

require_once $autoloadPath;

use FAIR\DID\Crypto\DidCodec;
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
$verificationPrivate = getenv('VERIFICATION_PRIVATE');
$verificationPublic = getenv('VERIFICATION_PUBLIC');
$artifactPath = getenv('ARTIFACT_PATH');

// Validate required inputs
if (empty($verificationPrivate) || empty($verificationPublic)) {
    echo "::error::Verification keys are required\n";
    exit(1);
}

if (empty($artifactPath) || !file_exists($artifactPath)) {
    echo "::error::Artifact path is invalid or file does not exist: {$artifactPath}\n";
    exit(1);
}

// Reconstruct verification key from private key
$verificationKey = EdDsaKey::from_private($verificationPrivate);

// Read artifact and compute hash
$artifactContents = file_get_contents($artifactPath);
$hash = hash('sha256', $artifactContents, true);

// Sign the hash
$signature = $verificationKey->sign($hash);

// Encode signature in multibase format
$encodedSignature = DidCodec::encode_signature($signature);

echo "::notice::Package signed successfully\n";
write_output('signature', $encodedSignature);
