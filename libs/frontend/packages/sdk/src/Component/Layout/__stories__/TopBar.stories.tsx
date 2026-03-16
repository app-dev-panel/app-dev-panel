import {TopBar} from '@app-dev-panel/sdk/Component/Layout/TopBar';
import type {Meta, StoryObj} from '@storybook/react';

const meta = {
    title: 'Layout/TopBar',
    component: TopBar,
    parameters: {layout: 'fullscreen'},
    tags: ['autodocs'],
} satisfies Meta<typeof TopBar>;

export default meta;
type Story = StoryObj<typeof meta>;

export const WithRequest: Story = {args: {method: 'GET', path: '/api/users', status: 200, duration: '143ms'}};

export const WithError: Story = {args: {method: 'POST', path: '/api/orders', status: 500, duration: '1.2s'}};

export const Empty: Story = {args: {}};
