import {useGetSettingsQuery} from '@app-dev-panel/sdk/API/Debug/Debug';
import {createPathMapper} from '@app-dev-panel/sdk/Helper/pathMapper';
import {useMemo} from 'react';

const emptyMapper = createPathMapper({});

/**
 * Hook that provides path mapping functions based on backend settings.
 * Fetches path mapping rules from the `/debug/api/settings` endpoint.
 */
export const usePathMapper = () => {
    const {data: settings} = useGetSettingsQuery();

    return useMemo(() => {
        if (!settings?.pathMapping || Object.keys(settings.pathMapping).length === 0) {
            return emptyMapper;
        }
        return createPathMapper(settings.pathMapping);
    }, [settings?.pathMapping]);
};
