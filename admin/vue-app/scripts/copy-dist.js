#!/usr/bin/env node
import { execSync } from 'child_process';
import path from 'path';
import { fileURLToPath } from 'url';
import fs from 'fs';

// Get the current directory
const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

// Define source and destination directories using absolute paths
const projectRoot = '/Users/johan/Local Sites/pluginvue/app/public/wp-content/plugins/WPschemaVUE';
const sourceDir = path.resolve(projectRoot, 'admin', 'vue-app', 'dist');
const destDir = path.resolve(projectRoot, 'admin', 'dist');

console.log('Current script directory:', __dirname);
console.log('Project root:', projectRoot);
console.log('Source directory:', sourceDir);
console.log('Destination directory:', destDir);

function logWithTimestamp(message) {
  const timestamp = new Date().toISOString();
  console.log(`[${timestamp}] ${message}`);
}

function ensureDirectoryExists(dirPath) {
  try {
    fs.mkdirSync(dirPath, { recursive: true });
    logWithTimestamp(`Ensured directory exists: ${dirPath}`);
  } catch (error) {
    logWithTimestamp(`Error creating directory ${dirPath}: ${error.message}`);
    throw error;
  }
}

function copyFiles() {
  try {
    // Ensure destination directory exists
    ensureDirectoryExists(destDir);

    // Use shell command to copy files
    const copyCommand = `cp -R "${sourceDir}"/* "${destDir}"/`;
    logWithTimestamp(`Executing copy command: ${copyCommand}`);
    
    execSync(copyCommand, { stdio: 'inherit' });

    // Verify copied files
    const copiedFiles = fs.readdirSync(destDir);
    logWithTimestamp('Copied files: ' + JSON.stringify(copiedFiles));
  } catch (error) {
    logWithTimestamp(`Error during copy process: ${error.message}`);
    process.exit(1);
  }
}

function main() {
  try {
    logWithTimestamp('Starting copy process...');

    // Check if source directory exists
    if (!fs.existsSync(sourceDir)) {
      logWithTimestamp(`Source directory ${sourceDir} does not exist`);
      process.exit(1);
    }

    // Remove existing destination directory
    try {
      fs.rmSync(destDir, { recursive: true, force: true });
      logWithTimestamp(`Cleaned destination directory: ${destDir}`);
    } catch (error) {
      logWithTimestamp('No existing destination directory to clean');
    }

    // Copy files
    copyFiles();

    logWithTimestamp('Copy process completed successfully!');
  } catch (error) {
    logWithTimestamp(`Unexpected error: ${error.message}`);
    process.exit(1);
  }
}

main();
