import { defineConfig } from 'vite';

const cssOutputMap = {
    'block-editor-style.css': 'css/block-editor.css',
    'admin-style.css': 'css/admin.css',
    'admin-dashboard-style.css': 'css/admin-dashboard.css',
    'frontend-style.css': 'css/frontend.css',
    'column-modal-style.css': 'css/column-modal.css',
};

const styleEntryNames = new Set(Object.keys(cssOutputMap).map((name) => name.replace('.css', '')));

export default defineConfig(({ mode }) => {
    const isProd = mode === 'production';
    return {
        base: '',
        publicDir: false,
        build: {
            outDir: '.',
            assetsDir: '',
            emptyOutDir: false,
            rollupOptions: {
                input: {
                    'block-editor': './src/js/block-editor.jsx',
                    'admin-dashboard': './src/js/admin-dashboard.js',
                    'admin-post': './src/js/admin-post.js',
                    'admin-quick-edit': './src/js/admin-quick-edit.js',
                    'admin-settings': './src/js/admin-settings.js',
                    'admin-widgets': './src/js/admin-widgets.js',
                    'column-modal': './src/js/column-modal.js',
                    counter: './src/js/counter.js',
                    frontend: './src/js/frontend.js',
                    'block-editor-style': './src/scss/block-editor.scss',
                    'admin-style': './src/scss/admin.scss',
                    'admin-dashboard-style': './src/scss/admin-dashboard.scss',
                    'frontend-style': './src/scss/frontend.scss',
                    'column-modal-style': './src/scss/column-modal.scss',
                },
                output: {
                    entryFileNames: 'js/[name].js',
                    chunkFileNames: 'js/[name].js',
                    assetFileNames: (assetInfo) => {
                        if (assetInfo.name && cssOutputMap[assetInfo.name]) {
                            return cssOutputMap[assetInfo.name];
                        }

                        return '[name][extname]';
                    },
                },
            },
            cssCodeSplit: true,
            sourcemap: false,
            minify: isProd ? 'esbuild' : false,
        },
        plugins: [
            {
                name: 'pvc-drop-style-js-stubs',
                generateBundle(_, bundle) {
                    for (const [fileName, chunk] of Object.entries(bundle)) {
                        if (chunk.type !== 'chunk') {
                            continue;
                        }

                        if (styleEntryNames.has(chunk.name)) {
                            delete bundle[fileName];

                            const mapName = `${fileName}.map`;
                            if (bundle[mapName]) {
                                delete bundle[mapName];
                            }
                        }
                    }
                },
            },
        ],
        esbuild: {
            jsxFactory: 'wp.element.createElement',
            jsxFragment: 'wp.element.Fragment',
        },
    };
});
