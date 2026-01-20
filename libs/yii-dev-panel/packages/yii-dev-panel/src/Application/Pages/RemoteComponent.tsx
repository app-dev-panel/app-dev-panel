import {Alert} from '@mui/material';
import {FullScreenCircularProgress} from '@yiisoft/yii-dev-panel-sdk/Component/FullScreenCircularProgress';
import React, {Suspense} from 'react';

type UseDynamicScriptProps = {
    url: string | undefined;
};
const useDynamicScript = ({url}: UseDynamicScriptProps) => {
    const [ready, setReady] = React.useState(false);
    const [failed, setFailed] = React.useState(false);

    React.useEffect(() => {
        if (!url) {
            return;
        }

        const element = document.createElement('script');

        element.src = url;
        element.type = 'text/javascript';
        element.async = true;

        setReady(false);
        setFailed(false);

        element.onload = () => {
            console.debug(`Dynamic Script Loaded: ${url}`);
            setReady(true);
        };

        element.onerror = () => {
            console.error(`Dynamic Script Error: ${url}`);
            setReady(false);
            setFailed(true);
        };

        document.head.appendChild(element);

        return () => {
            console.debug(`Dynamic Script Unloaded: ${url}`);
            document.head.removeChild(element);
        };
    }, [url]);

    return {
        ready,
        failed,
    };
};

const loadComponent = (scope: string, module: string) => async () => {
    /**
     * Initializes the shared scope. This fills it with known provided modules from this build and all remotes
     */
    // @ts-ignore
    await __webpack_init_sharing__('default');
    // @ts-ignore
    const container = window[scope];
    /**
     * Initialize the container, it may provide shared modules
     */
    // @ts-ignore
    await container.init(__webpack_share_scopes__.default);
    // @ts-ignore
    const factory = await window[scope].get(module);
    const Module = factory();
    return Module;
};

type ModuleLoaderProps = {
    module: string;
    url: string;
    scope: string;
    props: any;
};

const ModuleLoader = ({module, props, scope, url}: ModuleLoaderProps) => {
    const dynamicScript = useDynamicScript({
        url: module && url,
    });

    if (!module) {
        return <Alert severity="error">Module name cannot be empty</Alert>;
    }

    if (!dynamicScript.ready) {
        return <FullScreenCircularProgress />;
    }

    if (dynamicScript.failed) {
        return <Alert severity="error">Failed to load dynamic script: {url}</Alert>;
    }

    const Component = React.lazy(loadComponent(scope, module));

    return (
        <Suspense fallback={<FullScreenCircularProgress />}>
            <Component {...props} />
        </Suspense>
    );
};

export default ModuleLoader;
