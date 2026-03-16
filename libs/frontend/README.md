# Application Development Panel Monorepo

This is a monorepo for Application Development Panel and its SDK.

## Architecture

The project follows monorepository patterns. The project consists of several packages:

### `@app-dev-panel/panel`

[README](packages/app-dev-panel/README.md)

The SDK package. It is used to simplify creating applications or custom panels.

### `@app-dev-panel/sdk`

[README](packages/app-dev-panel-sdk/README.md)

The `toolbar` application. It is used to display the toolbar on the page.

The package is used to display the toolbar on the page. It can be used separately from the `app-dev-panel` application.

The `toolbar` application requires only `sdk` package.

### `@app-dev-panel/toolbar`

[README](packages/app-dev-toolbar/README.md)

The main application.

The `app` application requires both `sdk` and `toolbar` packages.

### Examples

#### [`examples/remote-panel`](examples/remote-panel)

Example of remote components that may be used as a custom panel.

Read more about how to work with remote components [here](docs/guide/en/shared_components.md).

#### Dependency graph

```mermaid
flowchart LR

    A[app-dev-panel] --> C(app-dev-panel-sdk)
    A[app-dev-panel] --> B
    B[app-dev-toolbar] --> C
```

## Documentation

- [Guide](docs/guide/en/README.md)

If you need help or have a question, the [Yii Forum](https://forum.yiiframework.com/c/yii-3-0/63) is a good place for that.
You may also check out other [Yii Community Resources](https://www.yiiframework.com/community).

## License

The Application Development Panel is free software. It is released under the terms of the BSD License.
Please see [`LICENSE`](./LICENSE.md) for more information.

Maintained by [Yii Software](https://www.yiiframework.com/).

## Support the project

[![Open Collective](https://img.shields.io/badge/Open%20Collective-sponsor-7eadf1?logo=open%20collective&logoColor=7eadf1&labelColor=555555)](https://opencollective.com/app-dev-panel)

## Follow updates

[![Official website](https://img.shields.io/badge/Powered_by-Yii_Framework-green.svg?style=flat)](https://www.yiiframework.com/)
[![Twitter](https://img.shields.io/badge/twitter-follow-1DA1F2?logo=twitter&logoColor=1DA1F2&labelColor=555555?style=flat)](https://twitter.com/yiiframework)
[![Telegram](https://img.shields.io/badge/telegram-join-1DA1F2?style=flat&logo=telegram)](https://t.me/yii3en)
[![Facebook](https://img.shields.io/badge/facebook-join-1DA1F2?style=flat&logo=facebook&logoColor=ffffff)](https://www.facebook.com/groups/yiitalk)
[![Slack](https://img.shields.io/badge/slack-join-1DA1F2?style=flat&logo=slack)](https://yiiframework.com/go/slack)
