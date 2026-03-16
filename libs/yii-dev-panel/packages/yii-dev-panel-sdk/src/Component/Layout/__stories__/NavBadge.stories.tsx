import type {Meta, StoryObj} from '@storybook/react';
import {NavBadge} from '@yiisoft/yii-dev-panel-sdk/Component/Layout/NavBadge';

const meta = {
    title: 'Layout/NavBadge',
    component: NavBadge,
    parameters: {layout: 'centered'},
    tags: ['autodocs'],
} satisfies Meta<typeof NavBadge>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Default: Story = {args: {count: 12}};
export const ErrorVariant: Story = {args: {count: 1, variant: 'error'}};
export const Zero: Story = {args: {count: 0}};
export const LargeCount: Story = {args: {count: 248}};
