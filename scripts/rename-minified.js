/**
 * Script to create minified versions of CSS files
 */

const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');

const srcDir = path.join(__dirname, '../src/scss');
const distDir = path.join(__dirname, '../dist/css');

async function createMinified() {
    try {
        console.log('Creating minified CSS files...');
        
        // Create minified versions with .min.css extension
        const cssFiles = ['minimal-admin', 'login'];
        
        for (const file of cssFiles) {
            const srcPath = path.join(srcDir, `${file}.scss`);
            const destPath = path.join(distDir, `${file}.min.css`);
            
            if (fs.existsSync(srcPath)) {
                execSync(`npx sass "${srcPath}":"${destPath}" --style compressed --no-source-map`, {
                    stdio: 'inherit'
                });
                console.log(`Created: ${file}.min.css`);
            }
        }
        
        console.log('Minification complete!');
        
    } catch (error) {
        console.error('Error creating minified files:', error);
        process.exit(1);
    }
}

createMinified();
