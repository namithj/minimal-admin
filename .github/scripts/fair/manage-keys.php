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

    // Mask the private keys in logs for security
    echo "::add-mask::{$newRotationPrivate}\n";
    echo "::add-mask::{$newVerificationPrivate}\n";

    // Write keys to step summary (not logs) for user to copy
    $summaryFile = getenv('GITHUB_STEP_SUMMARY');
    if ($summaryFile) {
        $summary = "\n## 🔐 CRYPTOGRAPHIC KEYS GENERATED\n\n";
        $summary .= "⚠️ **IMPORTANT**: These keys are shown only once. Copy them now!\n\n";
        $summary .= "### Setup Instructions\n\n";
        $summary .= "1. Go to your repository **Settings**\n";
        $summary .= "2. Navigate to **Secrets and variables** → **Actions**\n";
        $summary .= "3. Click **New repository secret** and add each of the following:\n\n";
        $summary .= "#### FAIR_ROTATION_KEY_PRIVATE\n";
        $summary .= "```\n{$newRotationPrivate}\n```\n\n";
        $summary .= "#### FAIR_ROTATION_KEY_PUBLIC\n";
        $summary .= "```\n{$newRotationPublic}\n```\n\n";
        $summary .= "#### FAIR_VERIFICATION_KEY_PRIVATE\n";
        $summary .= "```\n{$newVerificationPrivate}\n```\n\n";
        $summary .= "#### FAIR_VERIFICATION_KEY_PUBLIC\n";
        $summary .= "```\n{$newVerificationPublic}\n```\n\n";
        $summary .= "4. After adding all secrets, **re-run this workflow** to complete the publishing process.\n\n";
        $summary .= "---\n\n";
        $summary .= "ℹ️ These keys are **NOT** visible in the workflow logs for security reasons.\n";
        
        file_put_contents($summaryFile, $summary, FILE_APPEND);
    }

    echo "::notice::Cryptographic keys have been generated and added to the step summary.\n";
    echo "::notice::Keys are masked in logs for security.\n";
}
