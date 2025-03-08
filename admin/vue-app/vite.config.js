import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import { resolve } from 'path';

// https://vitejs.dev/config/
export default defineConfig({
  // Use relative paths for assets (important for WordPress plugins)
  base: './',
  plugins: [vue()],
  resolve: {
    alias: {
      '@': resolve(__dirname, 'src'),
    },
  },
  build: {
    sourcemap: true,
    // Generate manifest.json in outDir
    manifest: true,
    rollupOptions: {
      // Ensure proper output filenames for WordPress integration
      output: {
        entryFileNames: 'js/[name].js',
        chunkFileNames: 'js/[name]-[hash].js',
        assetFileNames: (assetInfo) => {
          if (assetInfo.name.endsWith('.css')) {
            return 'css/[name][extname]';
          }
          return 'assets/[name]-[hash][extname]';
        },
      },
    },
    outDir: '../../admin/dist',
    emptyOutDir: true,
  },
  server: {
    // Configure dev server for hot reload
    hmr: {
      protocol: 'ws',
      host: 'localhost',
    },
  },
});
