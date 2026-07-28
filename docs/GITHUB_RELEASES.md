# GitHub Releases and Release Manager

TalaKlase uses GitHub Releases as its application update source. Git remains a
developer workflow outside the application; the application does not fetch,
compare, or pull Git branches.

## Release Workflow

1. Prepare and test the release package.
2. Create a GitHub Release from a version tag such as `v1.0.0`.
3. Upload `manifest.json` and `talaklase.zip` as release assets.
4. Publish the release. Draft releases are not available to the application.
5. The Release Manager reads the manifest, compares versions, downloads the
   package, creates a backup, and installs the release.

## Required Assets

```text
manifest.json
talaklase.zip
```

The default manifest URL is:

```text
https://github.com/SunriseRaven/talaklase/releases/latest/download/manifest.json
```

## Manifest

The required field is a string `version`. Supported fields are:

```json
{
    "version": "1.0.0",
    "channel": "stable",
    "engine": "2.0",
    "release_date": "2026-07-29",
    "minimum_php": "8.2",
    "minimum_mysql": "10.4",
    "download_url": "https://github.com/SunriseRaven/talaklase/releases/latest/download/talaklase.zip",
    "release_notes": "Release notes"
}
```

Only `version` is required for compatibility. `download_url` is required when
installing a release.

## Backup and Installation

Before installation, the Release Manager creates a timestamped ZIP in
`backups/`, validates the package with `ZipArchive`, extracts it to a temporary
directory, and verifies that the package contains `index.php`.

The installer preserves these paths and files:

```text
config.php
.env
uploads/
storage/
logs/
backups/
.git/
```

Temporary files are removed after success or failure. A backup can be used for
manual rollback by restoring its contents after stopping the application.

## Channels

`stable` is the default release channel. `beta` may be used for pre-release
packages when a channel-aware release selection policy is introduced. Future
channels should be added through manifest and configuration policy rather than
Git branch selection.

## Version Comparison

The Release Manager removes leading non-numeric characters such as `v` and
uses PHP `version_compare()`. An update is available only when the published
version is newer than the installed version.

## Troubleshooting

- `No published GitHub Release was found.`: publish a non-draft release.
- `Unable to download release manifest.`: check connectivity and GitHub asset availability.
- `Release manifest URL is invalid.`: verify `TALAKLASE_RELEASE_MANIFEST_URL`.
- `Release manifest contains invalid JSON.`: validate the uploaded manifest.
- `The release package is not a valid ZIP archive.`: rebuild and re-upload `talaklase.zip`.
- `The release package is missing the application entry point.`: ensure `index.php` is included.

`update_check.php` and `do_update.php` remain the public endpoint names for
backward compatibility, even though their internal terminology and behavior
are now release-based.
