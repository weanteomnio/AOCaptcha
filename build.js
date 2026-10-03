const esbuild = require('esbuild');
const fs = require('fs');

fs.mkdirSync('dist', { recursive: true });

esbuild.buildSync({
  entryPoints: ['src/js/aocaptcha.js'],
  bundle: false,
  format: 'cjs',
  outfile: 'dist/aocaptcha.umd.js',
});

esbuild.buildSync({
  entryPoints: ['src/js/aocaptcha.js'],
  bundle: false,
  format: 'esm',
  outfile: 'dist/aocaptcha.esm.js',
  footer: { js: 'export default AOCaptcha;' },
});

esbuild.buildSync({
  entryPoints: ['src/css/aocaptcha.css'],
  bundle: false,
  minify: true,
  outfile: 'dist/aocaptcha.min.css',
});

console.log('Built dist/aocaptcha.umd.js, dist/aocaptcha.esm.js, dist/aocaptcha.min.css');
