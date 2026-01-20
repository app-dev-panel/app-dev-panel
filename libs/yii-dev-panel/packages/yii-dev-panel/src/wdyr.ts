import whyDidYouRender from '@welldone-software/why-did-you-render';
import * as React from 'react';
import * as ReactRedux from 'react-redux';

if (import.meta.env.DEV) {
    whyDidYouRender(React, {
        trackAllPureComponents: true,
        trackExtraHooks: [[ReactRedux, 'useSelector']],
    });
}
