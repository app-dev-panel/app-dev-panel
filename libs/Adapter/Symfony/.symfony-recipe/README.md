# Symfony Flex Recipe

This directory holds a pre-baked Flex recipe for `app-dev-panel/adapter-symfony`.
It is **not** consumed at runtime — its purpose is to be submitted to
[symfony/recipes-contrib](https://github.com/symfony/recipes-contrib) so that a
fresh `composer require app-dev-panel/adapter-symfony` automatically:

- Registers `AppDevPanelBundle` in `config/bundles.php` for `dev` + `test` envs
- Drops in `config/packages/app_dev_panel.yaml` with the default collector set
- Drops in `config/routes/app_dev_panel.php` mounting `/debug`, `/debug/api/*`, `/inspect/api/*`

Without the recipe a user must do all three steps by hand — see
`website/guide/adapters/symfony.md` for the manual instructions.

## Submitting the recipe

1. Fork [symfony/recipes-contrib](https://github.com/symfony/recipes-contrib)
2. Create a directory tree mirroring this one under
   `app-dev-panel/adapter-symfony/0.3/` (or whichever the next stable
   minor is — Flex matches recipes by version range)
3. Copy `manifest.json` + `config/` from this folder verbatim
4. Open a PR; the recipes maintainers verify it against a clean Symfony
   skeleton install. Once merged, the next `composer require
   app-dev-panel/adapter-symfony` invocation in any project will run the
   recipe automatically.

The recipe is non-aggressive: it does not modify any user files, only
creates the three new files above. It registers the bundle for `dev` +
`test` envs only, matching the behaviour we already document in the
manual install guide.

## Local testing

To preview the effect against a local Symfony skeleton:

```bash
composer create-project symfony/skeleton:^7.0 /tmp/sf-test
cd /tmp/sf-test
mkdir -p config/packages config/routes
cp /path/to/this/dir/config/packages/app_dev_panel.yaml config/packages/
cp /path/to/this/dir/config/routes/app_dev_panel.php config/routes/
# Manually merge manifest.json's bundles entry into config/bundles.php
composer require app-dev-panel/adapter-symfony
php bin/console cache:clear
php -S 127.0.0.1:8000 -t public
# Open http://127.0.0.1:8000/debug
```
