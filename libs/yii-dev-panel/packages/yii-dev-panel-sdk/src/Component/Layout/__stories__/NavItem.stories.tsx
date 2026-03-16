import type {Meta, StoryObj} from '@storybook/react';
import {NavItem} from '@yiisoft/yii-dev-panel-sdk/Component/Layout/NavItem';

const meta = {
    title: 'Layout/NavItem',
    component: NavItem,
    parameters: {layout: 'centered'},
    tags: ['autodocs'],
    decorators: [
        (Story) => (
            <div style={{width: 200}}>
                <Story />
            </div>
        ),
    ],
} satisfies Meta<typeof NavItem>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Default: Story = {args: {icon: 'description', label: 'Log'}};
export const Active: Story = {args: {icon: 'http', label: 'Request', active: true}};
export const WithBadge: Story = {args: {icon: 'storage', label: 'Database', badge: 4}};
export const WithErrorBadge: Story = {args: {icon: 'warning', label: 'Exception', badge: 1, badgeVariant: 'error'}};
export const LongLabel: Story = {args: {icon: 'filter_list', label: 'Middleware', badge: 42, active: true}};
