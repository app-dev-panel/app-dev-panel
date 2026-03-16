import {KeyValueTable} from '@app-dev-panel/sdk/Component/KeyValueTable';
import type {Meta, StoryObj} from '@storybook/react';

const meta = {
    title: 'Components/KeyValueTable',
    component: KeyValueTable,
    parameters: {layout: 'centered'},
    tags: ['autodocs'],
    decorators: [
        (Story) => (
            <div style={{width: 600}}>
                <Story />
            </div>
        ),
    ],
} satisfies Meta<typeof KeyValueTable>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Default: Story = {
    args: {
        rows: [
            {key: 'Controller', value: 'App\\Controller\\UserController::index'},
            {key: 'Route', value: 'api/users'},
            {key: 'Action', value: 'index'},
        ],
    },
};

export const Headers: Story = {
    args: {
        rows: [
            {key: 'Accept', value: 'application/json'},
            {key: 'Host', value: 'localhost:8080'},
            {key: 'Authorization', value: 'Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJz...'},
            {key: 'Content-Type', value: 'application/json'},
            {key: 'User-Agent', value: 'Mozilla/5.0 (X11; Linux x86_64)'},
            {key: 'X-Request-Id', value: 'f47ac10b-58cc-4372-a567-0e02b2c3d479'},
        ],
    },
};
