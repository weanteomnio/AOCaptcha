const esbuild = require('esbuild');
const fs = require('fs');

fs.mkdirSync('dist', { recursive: true });

const config = JSON.parse(fs.readFileSync('aocaptcha.config.json', 'utf8'));
const define = {
  'globalThis.__AOCAPTCHA_SHAPES__': JSON.stringify(config.shapes || {}),
  'globalThis.__AOCAPTCHA_THEME__': JSON.stringify(config.theme || {}),
};

esbuild.buildSync({
  entryPoints: ['src/js/aocaptcha.js'],
  bundle: false,
  format: 'cjs',
  define: define,
  outfile: 'dist/aocaptcha.umd.js',
});

esbuild.buildSync({
  entryPoints: ['src/js/aocaptcha.js'],
  bundle: false,
  format: 'esm',
  define: define,
  outfile: 'dist/aocaptcha.esm.js',
});

esbuild.buildSync({
  entryPoints: ['src/css/aocaptcha.css'],
  bundle: false,
  minify: true,
  outfile: 'dist/aocaptcha.min.css',
});

console.log('Built dist/aocaptcha.umd.js, dist/aocaptcha.esm.js, dist/aocaptcha.min.css');
