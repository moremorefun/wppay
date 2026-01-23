# WordPress.org SVN Assets

This folder contains assets for the WordPress.org plugin directory.
These files go into the `/assets/` folder in the SVN repository (NOT in trunk).

## Required Screenshots

Based on readme.txt, you need:

- `screenshot-1.png` - Admin settings page
- `screenshot-2.png` - Payment button in the block editor
- `screenshot-3.png` - Frontend payment button

**Recommended size:** 1200x900px or similar 4:3 ratio

## Plugin Icon (Recommended)

- `icon-128x128.png` - Small icon
- `icon-256x256.png` - Large icon (recommended)

Or use SVG:
- `icon.svg`

## Plugin Banner (Recommended)

- `banner-772x250.png` - Standard banner
- `banner-1544x500.png` - Retina banner (optional)

## Upload Instructions

After your plugin is approved:

```bash
cd paythefly-svn
cp ../assets-wp-org/*.png assets/
svn add assets/*
svn commit -m "Add plugin assets"
```
