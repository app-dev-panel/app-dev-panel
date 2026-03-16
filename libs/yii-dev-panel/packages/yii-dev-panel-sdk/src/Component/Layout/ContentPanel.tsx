import {Paper} from '@mui/material';
import {styled} from '@mui/material/styles';
import {componentTokens} from '@yiisoft/yii-dev-panel-sdk/Component/Theme/tokens';
import {PropsWithChildren} from 'react';

const ContentRoot = styled(Paper)(({theme}) => ({
    flex: 1,
    minWidth: 0,
    borderRadius: componentTokens.contentPanel.borderRadius,
    overflowY: 'auto',
    padding: theme.spacing(3.5, 4.5),
}));

export const ContentPanel = ({children}: PropsWithChildren) => {
    return <ContentRoot variant="outlined">{children}</ContentRoot>;
};
