# Build Commands

## Fresh Clone Setup

No dependency installation or compilation required.

## After Code Changes

Edit the PHP, JS or CSS files directly; no build step required.

## Release Build

1. `rtp-build --changelog` to copy to clipboard

2. Paste changelog prompt into agent and run

3. Set the release version in:

   - The `Version` plugin header in root file
   - The define or class var in the root file
   - The `Stable tag` in `readme.txt`.

   Leave these version changes uncommitted.

4. `rtp-build`
