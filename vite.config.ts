import { v4wp } from '@kucrut/vite-for-wp';
import { wp_scripts } from '@kucrut/vite-for-wp/plugins';
import react from '@vitejs/plugin-react';
import { resolve } from 'path';
import { defineConfig } from 'vite';

export default defineConfig({
  plugins: [
    v4wp({
      input: {
        admin: resolve(__dirname, 'src/admin/index.tsx'),
        block: resolve(__dirname, 'src/block/index.tsx'),
        shortcode: resolve(__dirname, 'src/shortcode/index.tsx'),
        fab: resolve(__dirname, 'src/fab/index.tsx'),
      },
      outDir: 'dist',
    }),
    wp_scripts(),
    react(),
  ],

  build: {
    sourcemap: process.env.NODE_ENV === 'development',
  },

  server: {
    port: 3000,
    cors: true,
    origin: 'http://localhost:3000',
  },

  resolve: {
    alias: {
      '@': resolve(__dirname, 'src'),
      '@shared': resolve(__dirname, 'src/shared'),
    },
  },
});
