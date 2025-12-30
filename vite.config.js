import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import path from 'path';
import fs from 'fs';

export default defineConfig(({ mode }) => {
  // Update PHP file with current mode
  const phpFile = path.resolve(__dirname, 'fluent-mailbox.php');
  if (fs.existsSync(phpFile)) {
    let content = fs.readFileSync(phpFile, 'utf8');
    const envString = mode === 'development' ? 'development' : 'production';
    const regex = /defined\('WP_ENV'\) \|\| define\('WP_ENV', '[^']+'\);/;

    if (regex.test(content)) {
      content = content.replace(regex, `defined('WP_ENV') || define('WP_ENV', '${envString}');`);
      fs.writeFileSync(phpFile, content);
      console.log(`Updated WP_ENV to '${envString}' in fluent-mailbox.php`);
    }
  }

  return {
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
    }
  };
});
