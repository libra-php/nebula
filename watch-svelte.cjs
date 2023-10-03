const chokidar = require('chokidar');
const { spawn } = require('child_process');
const livereload = require('livereload');

// Define the command to run (npx rollup -c)
const rollupCommand = 'npx';
const rollupArgs = ['rollup', '-c'];

// Define the directory to watch for changes
const watchDirectory = './resources/svelte/';

// Create a function to run the Rollup command
function runRollup() {
  const rollupProcess = spawn(rollupCommand, rollupArgs, { stdio: 'inherit' });

  rollupProcess.on('close', (code) => {
    if (code === 0) {
      console.log('Rollup build successful.');
      livereloadServer.refresh();
    } else {
      console.error(`Rollup build failed with code ${code}.`);
    }
  });
}

// Create a watcher to watch for changes in the directory
const watcher = chokidar.watch(watchDirectory, {
  ignored: /node_modules|\.git/,
  persistent: true,
});

// Create a livereload server
const livereloadServer = livereload.createServer();

// Add event listeners for changes
watcher
  .on('add', runRollup)
  .on('change', runRollup)
  .on('unlink', runRollup);

// Log when the watcher is ready
watcher.on('ready', () => {
  console.log(`Watching for changes in ${watchDirectory}...`);
});
