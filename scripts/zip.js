import { execSync } from 'child_process';
import { existsSync, mkdirSync, rmSync } from 'fs';
import { join, dirname } from 'path';
import { fileURLToPath } from 'url';

const __dirname = dirname(fileURLToPath(import.meta.url));
const rootDir = join(__dirname, '..');
const buildDir = join(rootDir, 'build');
const pluginName = 'paythefly-crypto-gateway';

// Files and directories to include in the zip
const includeList = [
  'paythefly-crypto-gateway.php',
  'readme.txt',
  'includes',
  'dist',
  'languages',
];

// Clean and create build directory
if (existsSync(buildDir)) {
  rmSync(buildDir, { recursive: true });
}
mkdirSync(buildDir);

const pluginDir = join(buildDir, pluginName);
mkdirSync(pluginDir);

// Copy files
for (const item of includeList) {
  const src = join(rootDir, item);
  const dest = join(pluginDir, item);

  if (!existsSync(src)) {
    console.warn(`Warning: ${item} does not exist, skipping...`);
    continue;
  }

  execSync(`cp -r "${src}" "${dest}"`);
  console.log(`Copied: ${item}`);
}

// Create zip file
const zipPath = join(buildDir, `${pluginName}.zip`);
execSync(`cd "${buildDir}" && zip -r "${pluginName}.zip" "${pluginName}"`);

console.log(`\nCreated: build/${pluginName}.zip`);
