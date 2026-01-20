# How to work with remote components

## About

Remote components are components that are located in a remote repository. 
They are not installed in the project, but are used as if they were provided from the backend. 
This feature allows you to create custom panels for the `yii-dev-panel` and render them as native components.

### How to test

First of all, you need to serve the `yii-dev-panel`.

Open a terminal and run:
```sh
cd packages/yii-dev-panel; npm start
```
It serves the yii-dev-panel project on `127.0.0.1:3000`. 

Then you need to serve your own built remote components. As an example you may use `examples/remote-panel` project.
Open a new terminal and run:
```sh
cd examples/remote-panel; npm run serve:dev
```
It serves an example project on `127.0.0.1:3002`.

Then open the file `packages/yii-dev-panel-sdk/src/Pages/Layout.tsx` and apply the following changes:
```diff
    {name: 'Open API', link: '/open-api'},
    // Uncomment to debug shared components
-    // {name: 'Shared', link: '/shared'},
+    {name: 'Shared', link: '/shared'},
```

Now you can open `http://localhost:3000/shared` and see the remote component in action.

