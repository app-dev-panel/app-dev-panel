# Yii Development Panel

Yii Development Panel – it is an application that aggregates a few different modules to help you develop and debug applications written with Yii 3.

## Installation

### NPM package (npmjs.com)

```shell
npm i @yiisoft/yii-dev-panel
```

### NPM package (GitHub Packages)

First you need to tell `npm` to use GitHub Packages registry for @yiisoft scope.
Add `@yiisoft:registry=https://npm.pkg.github.com` to `.npmrc` file or run the following command:
```shell
echo "\n@yiisoft:registry=https://npm.pkg.github.com" >> .npmrc
```

Then you can install the package:

```shell
npm i @yiisoft/yii-dev-panel
```

### Modules

- Debug
    - Repeating request by the only click
    - Collectors
        - LogCollector:
            - All collected logs during the request
        - EventCollector
            - All collected events during the request
        - ServiceCollector
            - All called services from the container during the request
        - ValidatorCollector
            - All validator calls with data, rules and results
        - QueueCollector
            - Pushed and consumed messages
        - WebAppInfoCollector
            - Some info about web configuration
        - RequestCollector
            - Collected info about the request
        - RouterCollector
            - All routes have been configured
        - MiddlewareCollector
            - All middlewares applied to the request
        - AssetCollector
            - All registered assets during the request
        - WebViewCollector
- Inspector
    - Routes
        - Find and inspect groups, routes, middlewares and the action
    - Parameters
        - Find and inspect parameters (`params.php`)
    - Configuration
        - Find and inspect container configurations
    - Container
        - Find and inspect services, tags, etc., available in the container
    - FileExplorer
        - Explore the application sources
    - Translations
        - Explore all registered translations
    - Commands (tests, analysis, composer scripts, any your own)
        - Run and see the result of any available commands (phpunit, codeception, psalm, composer scripts, any other)
    - Database
        - Simplify view the database configured with either Active Record or Cycle ORM
    - Git
        - See status, current branch, remotes and branch
        - Checkout any branch
    - PHP Info
        - See the whole result of `phpinfo()`
    - Composer (requiring packages, `composer.json` and `composer.lock` inspection)
        - Explore both `composer.json` and `composer.lock` files
        - Switch any package version
- Gii
    - Generators
        - Controller generator

## Usage

The application use HTTP API to work with the modules.

There are a few ways to use the Yii Dev Panel:

### Asset / Standalone application

To use the application you need to build the app and specify the URL your app running on.
Connect the apps and serve the built app any way you want: nginx, apache, node, php built-in server.

### PWA (Progressive Web Application)

Online-mode or as standalone application.

Open https://yiisoft.github.io/yii-dev-panel

1. Online-mode
    1. There is an app. Specify the PHP app URL and use Yii dev panel.
2. Standalone app
    1. Click "Install" button in the URL to install PWA application
    2. Run the application from the Applications
    3. Make sure your API works by clicking on "check mark"
    4. Use Yii dev panel

Both options also work with mobile phones.

## Contributing

### Prerequisites

First thing that you need is [`yiisoft/yii-debug-api`](https://github.com/yiisoft/yii-debug-api) running somewhere.

For example, you can clone [`yiisoft/demo`](https://github.com/yiisoft/demo) and serve `blog-api` or `blog`.

```shell
git clone git@github.com:yiisoft/demo demo
```

```shell
cd demo/blog-api
```

```shell
composer install
```

```shell
./yii serve
```

#### Installation

```shell
git clone git@github.com:yiisoft/yii-dev-panel yii-dev-panel
```
```shell
npm install
```

All necessary dependencies are now installed and ready to use. 

Serve `yii-dev-panel`

```shell
cd packages/yii-dev-panel
```
```shell
npm start
```

`yii-dev-panel` is now serving on http://localhost:3000

## Screenshots


<details>
  <summary>Debug</summary>
  <img src="docs/debug.collector.event.png" alt="image-description"/>
  <img src="docs/debug.collector.middleware.png" alt="image-description"/>
  <img src="docs/debug.collector.request.png" alt="image-description"/>
  <img src="docs/debug.collector.response.png" alt="image-description"/>
  <img src="docs/debug.collector.service.png" alt="image-description"/>
  <img src="docs/debug.logger.png" alt="image-description"/>
</details>

<details>
  <summary>Inspector</summary>
  <img src="docs/inspector.composer.png" alt="image-description"/>
  <img src="docs/inspector.definitions.png" alt="image-description"/>
  <img src="docs/inspector.events.png" alt="image-description"/>
  <img src="docs/inspector.files.png" alt="image-description"/>
  <img src="docs/inspector.git.png" alt="image-description"/>
  <img src="docs/inspector.parameters.png" alt="image-description"/>
  <img src="docs/inspector.php.png" alt="image-description"/>
  <img src="docs/inspector.router.png" alt="image-description"/>
  <img src="docs/inspector.router2.png" alt="image-description"/>
</details>
<details>
  <summary>Frames</summary>
  <img src="docs/frames.png" alt="image-description"/>
</details>
