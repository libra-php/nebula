const chokidar = require('chokidar');
const { spawn } = require('child_process');
const livereload = require('livereload');

// Define the directory to watch for changes
const watchDirectory = './app/';

// Create a livereload server
const livereloadServer = livereload.createServer();

// Add event listener for a change event
chokidar
  .watch(watchDirectory, {
    ignored: /node_modules|\.git/,
    persistent: true,
  })
  .on('change', path => {
    console.log(`Change detected in ${path}, refreshing...`);
    livereloadServer.refresh(path);
  });

// Log when the watcher is ready
console.log(`Watching for changes in ${watchDirectory}...`);
