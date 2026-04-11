import {EmptyState} from '@app-dev-panel/sdk/Component/EmptyState';
import {formatQueryError} from '@app-dev-panel/sdk/Helper/extractErrorMessage';
import {Button, Icon} from '@mui/material';

type QueryErrorStateProps = {error: unknown; title?: string; fallback?: string; onRetry?: () => void; icon?: string};

/**
 * Standard error state for failed RTK Query requests. Renders the error
 * message extracted from the response and an optional retry button.
 */
export const QueryErrorState = ({
    error,
    title = 'Failed to load',
    fallback,
    onRetry,
    icon = 'error_outline',
}: QueryErrorStateProps) => (
    <EmptyState
        icon={icon}
        title={title}
        description={formatQueryError(error, fallback)}
        severity="error"
        action={
            onRetry ? (
                <Button
                    variant="outlined"
                    size="small"
                    onClick={onRetry}
                    startIcon={<Icon sx={{fontSize: 16}}>refresh</Icon>}
                >
                    Retry
                </Button>
            ) : undefined
        }
    />
);
