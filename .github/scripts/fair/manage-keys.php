#!/usr/bin/env php
<?php
/**
 * FAIR Key Management Script
 *
 * Checks for existing cryptographic keys in environment variables.
 * If keys don't exist, generates new secp256k1 (rotation) and Ed25519 (verification) key pairs.
 *
 * Required environment variables:
 * - FAIR_ROTATION_KEY_PRIVATE
 * - FAIR_ROTATION_KEY_PUBLIC
 * - FAIR_VERIFICATION_KEY_PRIVATE
 * - FAIR_VERIFICATION_KEY_PUBLIC
 * - FAIR_DID (optional)
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
use FAIR\DID\Keys\EcKey;
use FAIR\DID\Keys\EdDsaKey;

/**
 * Write output to GitHub Actions output file.
 *
 * @param string $name  Output name.
 * @param string $value Output value.
 * @param bool   $multiline Whether the value is multiline.
 */
function write_output(string $name, string $value, bool $multiline = false): void {
    $outputFile = getenv('GITHUB_OUTPUT');
    if (!$outputFile) {
        return;
    }

    if ($multiline) {
        file_put_contents($outputFile, "{$name}<<EOF\n{$value}\nEOF\n", FILE_APPEND);
    } else {
        file_put_contents($outputFile, "{$name}={$value}\n", FILE_APPEND);
    }
}

// Get environment variables
$rotationPrivate = getenv('FAIR_ROTATION_KEY_PRIVATE');
$rotationPublic = getenv('FAIR_ROTATION_KEY_PUBLIC');
$verificationPrivate = getenv('FAIR_VERIFICATION_KEY_PRIVATE');
$verificationPublic = getenv('FAIR_VERIFICATION_KEY_PUBLIC');
$existingDid = getenv('FAIR_DID');

$keysExist = !empty($rotationPrivate) && !empty($rotationPublic)
          && !empty($verificationPrivate) && !empty($verificationPublic);

if ($keysExist) {
    echo "::notice::Using existing keys from secrets\n";

    // Output existing keys for use in subsequent steps
    write_output('keys_exist', 'true');
    write_output('rotation_private', $rotationPrivate, true);
    write_output('rotation_public', $rotationPublic, true);
    write_output('verification_private', $verificationPrivate, true);
    write_output('verification_public', $verificationPublic, true);

    if (!empty($existingDid)) {
        write_output('did', $existingDid);
        write_output('did_exists', 'true');
    } else {
        write_output('did_exists', 'false');
    }
} else {
    echo "::warning::Keys not found in secrets. Generating new keys...\n";
    echo "::warning::Please add the following secrets to your repository:\n";

    // Generate secp256k1 key pair for rotation
    $rotationKey = DidCodec::generate_key_pair();

    // Generate Ed25519 key pair for verification
    $verificationKey = DidCodec::generate_ed25519_key_pair();

    $newRotationPrivate = $rotationKey->encode_private();
    $newRotationPublic = $rotationKey->encode_public();
    $newVerificationPrivate = $verificationKey->encode_private();
    $newVerificationPublic = $verificationKey->encode_public();

    // Output generated keys
    write_output('keys_exist', 'false');
    write_output('rotation_private', $newRotationPrivate, true);
    write_output('rotation_public', $newRotationPublic, true);
    write_output('verification_private', $newVerificationPrivate, true);
    write_output('verification_public', $newVerificationPublic, true);
    write_output('did_exists', 'false');

    // Print instructions for adding secrets
    echo "\n======================================================\n";
    echo "IMPORTANT: Add these secrets to your GitHub repository:\n";
    echo "======================================================\n\n";
    echo "FAIR_ROTATION_KEY_PRIVATE:\n{$newRotationPrivate}\n\n";
    echo "FAIR_ROTATION_KEY_PUBLIC:\n{$newRotationPublic}\n\n";
    echo "FAIR_VERIFICATION_KEY_PRIVATE:\n{$newVerificationPrivate}\n\n";
    echo "FAIR_VERIFICATION_KEY_PUBLIC:\n{$newVerificationPublic}\n\n";
    echo "======================================================\n";
    echo "After adding these secrets, re-run this workflow.\n";
    echo "======================================================\n";
}
