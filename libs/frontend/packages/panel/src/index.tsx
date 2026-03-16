import reportWebVitals from '@app-dev-panel/panel/reportWebVitals';
import '@app-dev-panel/sdk/Component/Theme/material-icons.css';
import {Config} from '@app-dev-panel/sdk/Config';
import '@fontsource/inter/400.css';
import '@fontsource/inter/500.css';
import '@fontsource/inter/600.css';
import '@fontsource/inter/700.css';
import '@fontsource/jetbrains-mono/400.css';
import '@fontsource/jetbrains-mono/500.css';
import '@fontsource/material-icons';

Config.appEnv = import.meta.env.VITE_ENV;

import('@app-dev-panel/panel/bootstrap');

// If you want to start measuring performance in your app, pass a function
// to log results (for example: reportWebVitals(console.log))
// or send to an analytics endpoint. Learn more: https://bit.ly/CRA-vitals
reportWebVitals();
