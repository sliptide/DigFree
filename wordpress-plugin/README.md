# Dig Free — WordPress Plugin

This plugin lets you publish your vinyl listening picks from the [Dig Free](../README.md) app to your WordPress site.

## What it does

- Adds a **Listening Picks** custom post type to your WordPress admin
- Provides a REST API endpoint (`/wp-json/digfree/v1/entries`) that Dig Free calls to create, list, and delete picks
- Includes a `[digfree_history]` shortcode to display your listening history on any page or post

## Requirements

- WordPress 5.6+
- Permalinks set to anything other than **Plain** (Settings → Permalinks)
- A WordPress user with **Publish Posts** capability

## Installation

1. Download `digfree.zip` from this folder
2. In your WordPress admin, go to **Plugins → Add New → Upload Plugin**
3. Upload `digfree.zip` and click **Install Now**, then **Activate**

## Connecting Dig Free

### Generate an Application Password

1. In WordPress admin, go to **Users → Profile** (or **Users → All Users** → your account)
2. Scroll to **Application Passwords**
3. Enter a name (e.g. `Dig Free`) and click **Add New Application Password**
4. Copy the generated password — you'll only see it once

### Enter credentials in Dig Free

1. Open `index.html` in your browser
2. Go to **Settings → WordPress Publishing** (under Advanced)
3. Enter:
   - **Site URL** — your WordPress site URL (e.g. `https://yoursite.com`)
   - **WordPress Username** — your WP login username
   - **Application Password** — the password you just generated
4. Click **Save**, then **Test Connection**

## The `[digfree_history]` shortcode

Add this shortcode to any page or post to display your published listening picks.

### Basic usage

```
[digfree_history]
```

### Options

| Attribute | Default | Description |
|---|---|---|
| `limit` | `100` | Max results (non-paginated mode) |
| `per_page` | `0` | Entries per page; `> 0` enables pagination |
| `source` | _(all)_ | Filter: `daily` or `user` |
| `year` | _(all)_ | Filter by year, e.g. `year=2025` |
| `order` | `DESC` | Sort order: `ASC` or `DESC` by pick date |

### Examples

```
[digfree_history per_page=20]
[digfree_history source=daily year=2025]
[digfree_history limit=50 order=ASC]
```

## Upgrading from CrateDig plugin

If you previously used the **CrateDig** plugin (v1):

1. Deactivate and delete the old CrateDig plugin
2. Install and activate this Dig Free plugin
3. Existing picks stored with the old plugin are **not** automatically migrated — they lived in a separate `cratedig_pick` post type
4. Re-publish your picks from Dig Free to repopulate the `digfree_pick` post type

## License

MIT — see [../LICENSE](../LICENSE)
