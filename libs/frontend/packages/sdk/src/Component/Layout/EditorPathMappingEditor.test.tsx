import {screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {describe, expect, it, vi} from 'vitest';
import {renderWithProviders} from '../../test-utils';
import {EditorPathMappingEditor} from './EditorPathMappingEditor';

describe('EditorPathMappingEditor', () => {
    it('renders existing mappings as rows', () => {
        renderWithProviders(
            <EditorPathMappingEditor mapping={{'/app': '/Users/me/project', '/var/www': '/home/user/site'}} />,
        );
        expect(screen.getByDisplayValue('/app')).toBeInTheDocument();
        expect(screen.getByDisplayValue('/Users/me/project')).toBeInTheDocument();
        expect(screen.getByDisplayValue('/var/www')).toBeInTheDocument();
        expect(screen.getByDisplayValue('/home/user/site')).toBeInTheDocument();
    });

    it('adds a new row on Add click', async () => {
        const user = userEvent.setup();
        const onChange = vi.fn();
        renderWithProviders(<EditorPathMappingEditor mapping={{}} onChange={onChange} />);
        await user.click(screen.getByLabelText('Add mapping'));
        expect(screen.getByLabelText('Remote path 1')).toBeInTheDocument();
        expect(screen.getByLabelText('Local path 1')).toBeInTheDocument();
    });

    it('commits mapping on blur', async () => {
        const user = userEvent.setup();
        const onChange = vi.fn();
        renderWithProviders(<EditorPathMappingEditor mapping={{}} onChange={onChange} />);
        await user.click(screen.getByLabelText('Add mapping'));
        await user.type(screen.getByLabelText('Remote path 1'), '/app');
        await user.type(screen.getByLabelText('Local path 1'), '/home/user/project');
        await user.tab(); // blur the local field
        expect(onChange).toHaveBeenCalledWith({'/app': '/home/user/project'});
    });

    it('removes a row and commits the updated mapping', async () => {
        const user = userEvent.setup();
        const onChange = vi.fn();
        renderWithProviders(
            <EditorPathMappingEditor
                mapping={{'/app': '/local/app', '/vendor': '/local/vendor'}}
                onChange={onChange}
            />,
        );
        await user.click(screen.getByLabelText('Remove mapping 1'));
        expect(onChange).toHaveBeenLastCalledWith({'/vendor': '/local/vendor'});
    });

    it('ignores empty remote keys when committing', async () => {
        const user = userEvent.setup();
        const onChange = vi.fn();
        renderWithProviders(<EditorPathMappingEditor mapping={{'/app': '/local'}} onChange={onChange} />);
        // Clear the remote field entirely
        await user.clear(screen.getByLabelText('Remote path 1'));
        await user.tab();
        expect(onChange).toHaveBeenLastCalledWith({});
    });

    it('flags duplicate remote keys with an error helper', async () => {
        const user = userEvent.setup();
        renderWithProviders(<EditorPathMappingEditor mapping={{'/app': '/local/a'}} />);
        await user.click(screen.getByLabelText('Add mapping'));
        await user.type(screen.getByLabelText('Remote path 2'), '/app');
        // Both rows share the duplicate key, both helpers appear.
        expect(screen.getAllByText('Duplicate — last wins')).toHaveLength(2);
    });

    it('does not commit when mapping prop updates externally', () => {
        const onChange = vi.fn();
        const {rerender} = renderWithProviders(
            <EditorPathMappingEditor mapping={{'/app': '/local'}} onChange={onChange} />,
        );
        onChange.mockClear();
        rerender(
            <EditorPathMappingEditor mapping={{'/app': '/local', '/vendor': '/vendor-local'}} onChange={onChange} />,
        );
        // External mapping changed → rows sync, onChange must NOT fire (would cause a feedback loop).
        expect(onChange).not.toHaveBeenCalled();
        expect(screen.getByDisplayValue('/vendor')).toBeInTheDocument();
    });
});
