import { rename } from 'node:fs/promises';
import { build } from 'esbuild';

await build({
    entryPoints: ['resources/editor/index.js'],
    bundle: true,
    minify: true,
    legalComments: 'eof',
    outfile: 'resources/editor/build/editor.js',
});
await rename('resources/editor/build/editor.css', 'public/studio/editor.css');
