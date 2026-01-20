export const Config = {
    buildVersion: import.meta.env.VITE_BUILD_ID ? '#' + import.meta.env.VITE_BUILD_ID : 'development',
    appEnv: import.meta.env.VITE_ENV || 'development',
};
