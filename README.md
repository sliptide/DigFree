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

### Optional — AI commentary

Dig Free can generate a personal take, fun facts, and tracklist highlights for each album pick. Choose whichever provider you prefer — two of them are free:

| Provider | Model | Cost | Get a key |
|---|---|---|---|
| **Anthropic** | Claude | ~$0.01 / pick | [console.anthropic.com](https://console.anthropic.com) |
| **OpenAI** | GPT-4o mini | ~$0.01 / pick | [platform.openai.com](https://platform.openai.com) |
| **Google Gemini** | Gemini 2.5 Flash | Free tier available | [aistudio.google.com](https://aistudio.google.com) |
| **Groq** | Llama 3 | Free tier available | [console.groq.com](https://console.groq.com) |

Select your provider and paste your key in the setup screen or under **Settings → AI Commentary**. You're billed directly by the provider — Dig Free sends no data to any intermediate server.

### Optional — WordPress publishing

Publish your picks to a self-hosted WordPress site. Requires the [Dig Free WordPress plugin](wordpress-plugin/).

---

## Security model

All credentials (Discogs token, Claude API key, WordPress password) are:

- **Encrypted with AES-256** using the Web Crypto API
- **Stored only in your browser's IndexedDB** — device-bound, non-exportable key
- **Never transmitted to Dig Free** — there are no Dig Free servers
- Only sent directly to Discogs, your chosen AI provider, and your own WordPress site

Your collection data, listening history, and value snapshots are stored in `localStorage` in your browser. None of it leaves your device except through the APIs you configure.

---

## Features

| Feature | Description |
|---|---|
| **Daily Pick** | Random album from your collection with full Discogs metadata |
| **HD Artwork** | Full-resolution album art fetched via authenticated Discogs API, cached in IndexedDB |
| **AI Commentary** | AI-generated take, fun facts, and tracklist highlights — choose from Anthropic, OpenAI, Gemini, or Groq |
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

## Storage limits

| Collection size | History (capped at 1,000) | localStorage used | Status |
|---|---|---|---|
| 500 records | 1,000 entries | ~600 KB | ✅ Fine |
| 2,000 records | 1,000 entries | ~1 MB | ✅ Fine |
| 5,000 records | 1,000 entries | ~1.9 MB | ✅ Fine |
| 10,000 records | 1,000 entries | ~3.3 MB | ⚠️ Getting close |
| 17,000+ records | 1,000 entries | ~5 MB | ❌ Near limit |

Collection data and history are stored in `localStorage` (5 MB browser limit). History is hard-capped at 1,000 entries. The collection is the only real variable — collectors with fewer than 10,000 records are well within limits.

HD album artwork is cached in IndexedDB, which has no hard cap and is limited only by available disk space (~80 KB per cover).

---

## Browser support

Dig Free uses IndexedDB, Web Crypto API, and IntersectionObserver — all supported in:

- Chrome / Edge 102+
- Firefox 111+
- Safari 15.2+

---

## License

MIT — see [LICENSE](LICENSE)
