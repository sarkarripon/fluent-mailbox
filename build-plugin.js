const { execSync } = require('child_process');
const fs = require('fs');
const path = require('path');

const pluginName = 'fluent-mailbox';
const rootDir = __dirname;
const buildDir = path.join(rootDir, 'build');
const destDir = path.join(buildDir, pluginName);

// Ensure build directory exists and is empty of previous build artifacts
if (fs.existsSync(buildDir)) {
    fs.rmSync(buildDir, { recursive: true, force: true });
}
fs.mkdirSync(destDir, { recursive: true });

// Run npm build
console.log('Running npm run build...');
try {
    execSync('npm run build', { stdio: 'inherit', cwd: rootDir });
} catch (error) {
    console.error('Build failed:', error);
    process.exit(1);
}

// Files/Directories to copy
const includeList = [
    'app',
    'assets',
    'vendor',
    'fluent-mailbox.php',
    'composer.json',
    'composer.lock',
    'readme.txt'
];

console.log('Copying files...');
for (const item of includeList) {
    const src = path.join(rootDir, item);
    const dest = path.join(destDir, item);

    if (fs.existsSync(src)) {
        // Using cp -R to copy recursively and preserve file attributes
        execSync(`cp -R "${src}" "${dest}"`);
    } else {
        console.warn(`Warning: ${item} not found, skipping.`);
    }
}

// Create Zip
console.log('Creating zip archive...');
try {
    const zipFileName = `${pluginName}.zip`;
    // Execute zip command inside build dir to have correct folder structure in zip
    execSync(`zip -r ${zipFileName} ${pluginName}`, { cwd: buildDir, stdio: 'inherit' });
    console.log(`Successfully created ${path.join(buildDir, zipFileName)}`);
} catch (error) {
    console.error('Zipping failed:', error);
    process.exit(1);
}

// Cleanup unzipped folder
console.log('Cleaning up...');
fs.rmSync(destDir, { recursive: true, force: true });

console.log('Build completed successfully.');
