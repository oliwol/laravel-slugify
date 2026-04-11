import { defineConfig } from 'vitepress'

export default defineConfig({
  title: 'Laravel Slugify',
  description: 'Automatic slug generation for Laravel Eloquent models',
  base: '/laravel-slugify/',

  head: [
    ['link', { rel: 'icon', type: 'image/svg+xml', href: '/laravel-slugify/logo.svg' }],
  ],

  themeConfig: {
    nav: [
      { text: 'Guide', link: '/guide/getting-started' },
      { text: 'API Reference', link: '/api/reference' },
      {
        text: 'Packagist',
        link: 'https://packagist.org/packages/oliwol/laravel-slugify',
      },
    ],

    sidebar: [
      {
        text: 'Guide',
        items: [
          { text: 'Getting Started', link: '/guide/getting-started' },
          { text: 'Configuration', link: '/guide/configuration' },
          { text: 'Features', link: '/guide/features' },
          { text: 'Migrating from Spatie', link: '/guide/migrating-from-spatie' },
        ],
      },
      {
        text: 'Reference',
        items: [
          { text: 'API Reference', link: '/api/reference' },
        ],
      },
    ],

    socialLinks: [
      { icon: 'github', link: 'https://github.com/oliwol/laravel-slugify' },
    ],

    footer: {
      message: 'Released under the MIT License.',
      copyright: 'Copyright &copy; Oliver Wolschke',
    },

    search: {
      provider: 'local',
    },
  },
})
