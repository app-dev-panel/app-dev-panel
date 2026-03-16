import {CollectorSidebar} from '@app-dev-panel/sdk/Component/Layout/CollectorSidebar';
import type {Meta, StoryObj} from '@storybook/react';

const sampleItems = [
    {key: 'request', icon: 'http', label: 'Request'},
    {key: 'log', icon: 'description', label: 'Log', badge: 12},
    {key: 'database', icon: 'storage', label: 'Database', badge: 4},
    {key: 'events', icon: 'bolt', label: 'Events', badge: 8},
    {key: 'exception', icon: 'warning', label: 'Exception', badge: 1, badgeVariant: 'error' as const},
    {key: 'middleware', icon: 'filter_list', label: 'Middleware', badge: 6},
    {key: 'service', icon: 'inventory_2', label: 'Service', badge: 24},
    {key: 'timeline', icon: 'timeline', label: 'Timeline'},
    {key: 'memory', icon: 'memory', label: 'Memory'},
];

const meta = {
    title: 'Layout/CollectorSidebar',
    component: CollectorSidebar,
    parameters: {layout: 'centered'},
    tags: ['autodocs'],
} satisfies Meta<typeof CollectorSidebar>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Default: Story = {
    args: {items: sampleItems, activeKey: 'request', onItemClick: () => {}, onOverviewClick: () => {}},
};

export const NoActiveItem: Story = {args: {items: sampleItems, onItemClick: () => {}, onOverviewClick: () => {}}};

export const FewItems: Story = {
    args: {
        items: [
            {key: 'request', icon: 'http', label: 'Request'},
            {key: 'log', icon: 'description', label: 'Log', badge: 3},
        ],
        activeKey: 'log',
        onItemClick: () => {},
    },
};
