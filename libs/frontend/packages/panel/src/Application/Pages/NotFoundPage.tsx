import {DuckIcon} from '@app-dev-panel/sdk/Component/DuckIcon';
import {InfoBox} from '@app-dev-panel/sdk/Component/InfoBox';
import {Link, Typography} from '@mui/material';
import {useNavigate} from 'react-router-dom';

export const NotFoundPage = () => {
    const navigate = useNavigate();

    return (
        <InfoBox
            title={'Unknown page'}
            text={
                <>
                    <Typography>Looks like the page doesn't exist anymore.</Typography>
                    <Typography>
                        Try to&nbsp;
                        <Link onClick={() => navigate(-1)}>go back</Link>&nbsp; or open{' '}
                        <Link href="/">the main page</Link>.
                    </Typography>
                </>
            }
            icon={<DuckIcon />}
            severity={'error'}
        />
    );
};
