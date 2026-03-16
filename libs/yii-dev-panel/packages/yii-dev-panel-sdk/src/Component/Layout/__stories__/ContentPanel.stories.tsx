import {Typography} from '@mui/material';
import type {Meta, StoryObj} from '@storybook/react';
import {KeyValueTable} from '@yiisoft/yii-dev-panel-sdk/Component/KeyValueTable';
import {ContentPanel} from '@yiisoft/yii-dev-panel-sdk/Component/Layout/ContentPanel';
import {SectionTitle} from '@yiisoft/yii-dev-panel-sdk/Component/SectionTitle';

const meta = {
    title: 'Layout/ContentPanel',
    component: ContentPanel,
    parameters: {layout: 'centered'},
    tags: ['autodocs'],
    decorators: [
        (Story) => (
            <div style={{width: 800}}>
                <Story />
            </div>
        ),
    ],
} satisfies Meta<typeof ContentPanel>;

export default meta;
type Story = StoryObj<typeof meta>;

export const WithSampleContent: Story = {
    render: () => (
        <ContentPanel>
            <Typography variant="h4" gutterBottom>
                GET /api/users
            </Typography>
            <SectionTitle>General</SectionTitle>
            <KeyValueTable
                rows={[
                    {key: 'Controller', value: 'App\\Controller\\UserController::index'},
                    {key: 'Route', value: 'api/users'},
                    {key: 'Action', value: 'index'},
                ]}
            />
            <SectionTitle>Request Headers</SectionTitle>
            <KeyValueTable
                rows={[
                    {key: 'Accept', value: 'application/json'},
                    {key: 'Host', value: 'localhost:8080'},
                    {key: 'Content-Type', value: 'application/json'},
                ]}
            />
        </ContentPanel>
    ),
};

export const Empty: Story = {
    render: () => (
        <ContentPanel>
            <Typography color="text.secondary">No content selected</Typography>
        </ContentPanel>
    ),
};
