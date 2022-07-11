const fs = require('fs');
const path = require('path');
const { execFileSync } = require('child_process');

let files = fs.readdirSync(__dirname);

files = files.filter((file) => {
  if (file === path.basename(__filename)) return false;
  if (file.endsWith('.php')) return true;
  return false;
});

files.forEach((file) => {
  console.log(`Running ${file}\n`);
  try {
    const stdout = execFileSync('php', [file], { cwd: __dirname });
    console.log(`${stdout.toString()}\n`);
  } catch (e) {
    console.error(`${e.message}\n`);
  }
});
