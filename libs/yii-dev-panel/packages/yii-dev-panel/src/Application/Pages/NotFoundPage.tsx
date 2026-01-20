import {Link, Typography} from '@mui/material';
import {InfoBox} from '@yiisoft/yii-dev-panel-sdk/Component/InfoBox';
import {YiiIcon} from '@yiisoft/yii-dev-panel-sdk/Component/SvgIcon/YiiIcon';
import {useNavigate, useMatches, useLocation} from 'react-router-dom';

export const NotFoundPage = () => {
    const navigate = useNavigate();
    const location = useLocation();
    const matches = useMatches();
    console.log('location', location);
    console.log('matches', matches);

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
            icon={<YiiIcon monochromeColor={'#e65151'} />}
            severity={'error'}
        />
    );
};
