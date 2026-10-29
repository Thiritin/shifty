import { defineConfig } from 'vitepress'

export default defineConfig({
  title: 'Shifty',
  description: 'Shift planning for volunteer teams',
  base: '/shifty/',
  cleanUrls: true,
  ignoreDeadLinks: 'localhostLinks',
  head: [['meta', { name: 'theme-color', content: '#0f766e' }]],
  themeConfig: {
    nav: [
      { text: 'Docs', link: '/deployment' },
      { text: 'Releases', link: 'https://github.com/Thiritin/shifty/releases' },
    ],
    sidebar: [
      {
        text: 'Guide',
        items: [
          { text: 'Deployment', link: '/deployment' },
          { text: 'Configuration', link: '/configuration' },
          { text: 'Authentication', link: '/authentication' },
          { text: 'Development', link: '/development' },
        ],
      },
    ],
    socialLinks: [{ icon: 'github', link: 'https://github.com/Thiritin/shifty' }],
    editLink: { pattern: 'https://github.com/Thiritin/shifty/edit/main/docs/:path' },
    footer: { message: 'Released under the MIT license.' },
  },
})
