# Advance Deposits for WooCommerce

A GPL-licensed WooCommerce extension that collects a configurable online deposit for selected shipping zones and records the balance due on delivery.

## Requirements

- WordPress 6.5 or newer
- PHP 7.4 or newer
- WooCommerce 8.0 or newer
- Classic WooCommerce checkout
- At least one enabled online payment gateway

## Setup

Activate the plugin, then open **WooCommerce > Advance payments**. Choose a percentage or fixed deposit and select the applicable WooCommerce shipping zones.

For full usage, compatibility, and release details, see [`readme.txt`](readme.txt).

## Build an installable ZIP

Run:

```bash
./bin/build-release.sh
```

The versioned ZIP and its SHA-256 checksum are written to `dist/`. The archive
contains a single `advance-deposits-for-woocommerce` directory and excludes
repository-only files.

## Create a GitHub release

The `Build and release plugin` workflow always uploads the installable ZIP as a
workflow artifact. Pushing a version tag also creates a GitHub release and
attaches the ZIP and checksum:

```bash
git tag v2.0.0
git push origin v2.0.0
```

Run the workflow manually from GitHub Actions when only a downloadable build
artifact (and no tagged release) is needed.

## License

GPL-2.0-or-later. See [`LICENSE`](LICENSE).
