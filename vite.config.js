import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import path from 'path';

export default defineConfig({
  plugins: [vue()],
  build: {
    outDir: 'assets',
    emptyOutDir: true,
    manifest: false,
    rollupOptions: {
      input: 'resources/js/main.js',
      output: {
        entryFileNames: 'js/main.js',
        chunkFileNames: 'js/[name].js',
        assetFileNames: (assetInfo) => {
          if (assetInfo.name && assetInfo.name.endsWith('.css')) {
            return 'css/style.css';
          }
          return 'assets/[name].[ext]';
        }
      },
    },
  },
  server: {
    port: 4005,
    strictPort: true,
    hmr: {
      host: 'localhost',
    },
    cors: {
      origin: "*",
      methods: ["GET"],
      allowedHeaders: ["Content-Type", "Authorization"],
  },
  },
});
