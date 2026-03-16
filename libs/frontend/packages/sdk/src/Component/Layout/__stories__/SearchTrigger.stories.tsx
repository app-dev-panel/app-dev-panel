import {SearchTrigger} from '@app-dev-panel/sdk/Component/Layout/SearchTrigger';
import type {Meta, StoryObj} from '@storybook/react';

const meta = {
    title: 'Layout/SearchTrigger',
    component: SearchTrigger,
    parameters: {layout: 'centered'},
    tags: ['autodocs'],
} satisfies Meta<typeof SearchTrigger>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Default: Story = {args: {}};
