/**
 * Script to generate RTL versions of CSS files
 */

const fs = require('fs');
const path = require('path');

const distDir = path.join(__dirname, '../dist/css');

async function generateRTL() {
    try {
        console.log('Generating RTL stylesheets...');
        
        // Find all CSS files (excluding already RTL files and minified)
        const allFiles = fs.readdirSync(distDir);
        const cssFiles = allFiles.filter(file => 
            file.endsWith('.css') && 
            !file.includes('-rtl.css') && 
            !file.includes('.min.css')
        );
        
        for (const file of cssFiles) {
            const srcPath = path.join(distDir, file);
            const destPath = path.join(distDir, file.replace('.css', '-rtl.css'));
            
            // Read the CSS file
            const css = fs.readFileSync(srcPath, 'utf8');
            
            // Generate RTL version using basic transformations
            let rtlCss = css
                // Swap left/right in property names
                .replace(/padding-left/g, 'padding-__RIGHT__')
                .replace(/padding-right/g, 'padding-left')
                .replace(/padding-__RIGHT__/g, 'padding-right')
                .replace(/margin-left/g, 'margin-__RIGHT__')
                .replace(/margin-right/g, 'margin-left')
                .replace(/margin-__RIGHT__/g, 'margin-right')
                .replace(/border-left/g, 'border-__RIGHT__')
                .replace(/border-right/g, 'border-left')
                .replace(/border-__RIGHT__/g, 'border-right')
                .replace(/left:/g, '__RIGHT__:')
                .replace(/right:/g, 'left:')
                .replace(/__RIGHT__:/g, 'right:')
                // Swap text-align values
                .replace(/text-align:\s*left/g, 'text-align: __RIGHT__')
                .replace(/text-align:\s*right/g, 'text-align: left')
                .replace(/text-align: __RIGHT__/g, 'text-align: right')
                // Swap float values
                .replace(/float:\s*left/g, 'float: __RIGHT__')
                .replace(/float:\s*right/g, 'float: left')
                .replace(/float: __RIGHT__/g, 'float: right');
            
            // Write RTL version
            fs.writeFileSync(destPath, rtlCss);
            
            console.log(`Generated: ${file.replace('.css', '-rtl.css')}`);
        }
        
        console.log('RTL generation complete!');
        
    } catch (error) {
        console.error('Error generating RTL:', error);
        process.exit(1);
    }
}

generateRTL();
