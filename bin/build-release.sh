#!/usr/bin/env bash

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PLUGIN_FILE="${ROOT_DIR}/wooadvancepay.php"
SLUG="advance-deposits-for-woocommerce"
DIST_DIR="${ROOT_DIR}/dist"

if ! command -v zip >/dev/null 2>&1; then
	echo "Error: zip is required to build the release package." >&2
	exit 1
fi

VERSION="$(sed -nE 's/^ \* Version:[[:space:]]+([^[:space:]]+).*/\1/p' "${PLUGIN_FILE}" | head -n 1)"
STABLE_TAG="$(sed -nE 's/^Stable tag:[[:space:]]+([^[:space:]]+).*/\1/p' "${ROOT_DIR}/readme.txt" | head -n 1)"

if [[ -z "${VERSION}" ]]; then
	echo "Error: plugin version could not be read from wooadvancepay.php." >&2
	exit 1
fi

if [[ "${VERSION}" != "${STABLE_TAG}" ]]; then
	echo "Error: plugin version ${VERSION} does not match readme stable tag ${STABLE_TAG}." >&2
	exit 1
fi

EXPECTED_VERSION="${EXPECTED_VERSION:-}"
EXPECTED_VERSION="${EXPECTED_VERSION#v}"
if [[ -n "${EXPECTED_VERSION}" && "${VERSION}" != "${EXPECTED_VERSION}" ]]; then
	echo "Error: release tag version ${EXPECTED_VERSION} does not match plugin version ${VERSION}." >&2
	exit 1
fi

PACKAGE="${DIST_DIR}/${SLUG}-${VERSION}.zip"
STAGING_DIR="$(mktemp -d)"
trap 'rm -rf "${STAGING_DIR}"' EXIT

mkdir -p "${DIST_DIR}" "${STAGING_DIR}/${SLUG}/includes" "${STAGING_DIR}/${SLUG}/languages"

cp "${ROOT_DIR}/wooadvancepay.php" "${STAGING_DIR}/${SLUG}/"
cp "${ROOT_DIR}/includes/"*.php "${STAGING_DIR}/${SLUG}/includes/"
cp "${ROOT_DIR}/languages/wooadvancepay.pot" "${STAGING_DIR}/${SLUG}/languages/"
cp "${ROOT_DIR}/readme.txt" "${ROOT_DIR}/README.md" "${ROOT_DIR}/LICENSE" "${STAGING_DIR}/${SLUG}/"

rm -f "${PACKAGE}" "${PACKAGE}.sha256"
(
	cd "${STAGING_DIR}"
	zip -q -9 -r "${PACKAGE}" "${SLUG}"
)

if command -v sha256sum >/dev/null 2>&1; then
	(
		cd "${DIST_DIR}"
		sha256sum "$(basename "${PACKAGE}")" > "$(basename "${PACKAGE}").sha256"
	)
fi

echo "Built ${PACKAGE}"
