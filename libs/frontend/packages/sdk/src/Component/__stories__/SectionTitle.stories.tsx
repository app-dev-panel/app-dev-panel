import {SectionTitle} from '@app-dev-panel/sdk/Component/SectionTitle';
import type {Meta, StoryObj} from '@storybook/react';

const meta = {
    title: 'Components/SectionTitle',
    component: SectionTitle,
    parameters: {layout: 'centered'},
    tags: ['autodocs'],
    decorators: [
        (Story) => (
            <div style={{width: 600}}>
                <Story />
            </div>
        ),
    ],
} satisfies Meta<typeof SectionTitle>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Default: Story = {args: {children: 'Request Headers'}};
export const Short: Story = {args: {children: 'General'}};
export const Long: Story = {args: {children: 'Response Cookies and Session Data'}};
