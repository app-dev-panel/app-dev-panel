import type {Meta, StoryObj} from '@storybook/react';
import {RequestPill} from '@yiisoft/yii-dev-panel-sdk/Component/Layout/RequestPill';

const meta = {
    title: 'Layout/RequestPill',
    component: RequestPill,
    parameters: {layout: 'centered'},
    tags: ['autodocs'],
} satisfies Meta<typeof RequestPill>;

export default meta;
type Story = StoryObj<typeof meta>;

export const GetSuccess: Story = {args: {method: 'GET', path: '/api/users', status: 200, duration: '143ms'}};
export const PostCreated: Story = {args: {method: 'POST', path: '/api/orders', status: 201, duration: '342ms'}};
export const DeleteNotFound: Story = {args: {method: 'DELETE', path: '/api/users/99', status: 404, duration: '12ms'}};
export const ServerError: Story = {args: {method: 'POST', path: '/api/checkout', status: 500, duration: '1.2s'}};
export const LongPath: Story = {
    args: {method: 'GET', path: '/api/v2/organizations/123/members?page=1&limit=50', status: 200, duration: '89ms'},
};
