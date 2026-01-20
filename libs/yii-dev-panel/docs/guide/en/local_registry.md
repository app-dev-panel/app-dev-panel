# How to use Local registry (`verdaccio`)

## Purpose

- Test the publishing process locally.
- Test the installation process locally.

## How to use

To be able to use the local registry, you need:

1. Run the local registry server

```shell
npx verdaccio
```

That will output:

```text
info --- config file  - /Users/xepozz/.config/verdaccio/config.yaml
info --- "crypt" algorithm is deprecated consider switch to "bcrypt". Read more: https://github.com/verdaccio/monorepo/pull/580
info --- plugin successfully loaded: verdaccio-htpasswd
info --- plugin successfully loaded: verdaccio-audit
warn --- http address - http://localhost:4873/ - verdaccio/5.23.2
```

2. Add the local registry to your `~/.npmrc` file:

```text
registry=http://localhost:4873/ <-- the url from the previous step
```

3. Run `npm adduser` and done this step.
4. Run `npm publish` in the root of the package you want to publish or use `npm publish -ws` to publish all packages in the monorepo.
