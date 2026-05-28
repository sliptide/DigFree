# Dig Free

**Your Discogs collection, one record at a time.**

Dig Free is a single-file browser app that picks a record from your Discogs collection every day, generates AI-powered commentary about it, tracks your collection's value over time, and lets you publish your listening history to WordPress.

No servers. No accounts. No installs. Just open the file.

---

## Quick start

1. **Download** [`index.html`](index.html)
2. **Open it** in Chrome, Firefox, or Safari
3. **Connect** your Discogs account (see below)
4. **Pick today's album** — or let it surprise you

---

## What you need

### Required — Discogs account

1. Sign up at [discogs.com](https://www.discogs.com) if you don't have an account
2. Go to [discogs.com/settings/developers](https://www.discogs.com/settings/developers)
3. Click **Generate new token** and copy it
4. Enter your username and token in Dig Free's setup screen

### Optional — Claude AI commentary

Dig Free can generate a personal take, fun facts, and tracklist highlights for each album pick using Claude.

1. Get an API key at [console.anthropic.com](https://console.anthropic.com)
2. Enter it in **Settings → AI Features**

Cost: ~$0.01 per album pick. You're billed directly by Anthropic — Dig Free sends no data to any intermediate server.

### Optional — WordPress publishing

Publish your picks to a self-hosted WordPress site. Requires the [Dig Free WordPress plugin](wordpress-plugin/).

---

## Security model

All credentials (Discogs token, Claude API key, WordPress password) are:

- **Encrypted with AES-256** using the Web Crypto API
- **Stored only in your browser's IndexedDB** — device-bound, non-exportable key
- **Never transmitted to Dig Free** — there are no Dig Free servers
- Only sent directly to Discogs, Anthropic, and your own WordPress site

Your collection data, listening history, and value snapshots are stored in `localStorage` in your browser. None of it leaves your device except through the APIs you configure.

---

## Features

| Feature | Description |
|---|---|
| **Daily Pick** | Random album from your collection with full Discogs metadata |
| **HD Artwork** | Full-resolution album art fetched via authenticated Discogs API, cached in IndexedDB |
| **AI Commentary** | Claude-generated take, fun facts, and tracklist highlights (requires API key) |
| **History** | Searchable log of every pick with editable dates and notes |
| **Browse** | Search and filter your full collection; manually add any record to history |
| **Value Tracking** | Snapshot your collection's market value over time with a min/median/max chart |
| **CSV Import** | Import historical value data from a spreadsheet |
| **WordPress Publish** | Push picks to your WordPress site using the included plugin |
| **Export** | Download your history and value data as JSON backups |

---

## Optional: WordPress integration

See [`wordpress-plugin/README.md`](wordpress-plugin/README.md) for setup instructions.

The `[digfree_history]` shortcode displays your published listening history on any WordPress page.

---

## Browser support

Dig Free uses IndexedDB, Web Crypto API, and IntersectionObserver — all supported in:

- Chrome / Edge 102+
- Firefox 111+
- Safari 15.2+

---

## License

MIT — see [LICENSE](LICENSE)
