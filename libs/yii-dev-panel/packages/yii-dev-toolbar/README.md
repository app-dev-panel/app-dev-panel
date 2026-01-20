# Yii Development Panel | Toolbar

The toolbar application. It is used to display the toolbar on the page. It can be used separately from the `app` application.

## Installation

### NPM package (npmjs.com)

```shell
npm i @yiisoft/yii-dev-toolbar
```

### NPM package (GitHub Packages)

First you need to tell `npm` to use GitHub Packages registry for @yiisoft scope.
Add `@yiisoft:registry=https://npm.pkg.github.com` to `.npmrc` file or run the following command:

```shell
echo "\n@yiisoft:registry=https://npm.pkg.github.com" >> .npmrc
```

Then you can install the package:

```shell
npm i @yiisoft/yii-dev-toolbar
```

### CDN

Add the following code to the page to display the toolbar:

```html
<div id="yii-dev-toolbar" style="flex: 1"></div>
<script src="https://yiisoft.github.io/yii-dev-panel/toolbar/bundle.js"></script>
<link rel="stylesheet" href="https://yiisoft.github.io/yii-dev-panel/toolbar/bundle.css" />
```
