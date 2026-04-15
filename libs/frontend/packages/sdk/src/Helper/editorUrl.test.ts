import {describe, expect, it} from 'vitest';
import {type EditorConfig, buildEditorUrl, defaultEditorConfig, editorPresetLabels} from './editorUrl';

describe('buildEditorUrl', () => {
    it('returns null when editor is none', () => {
        expect(buildEditorUrl(defaultEditorConfig, '/src/app.php', 42)).toBeNull();
    });

    it('builds phpstorm URL', () => {
        const config: EditorConfig = {...defaultEditorConfig, editor: 'phpstorm'};
        expect(buildEditorUrl(config, '/src/app.php', 42)).toBe('phpstorm://open?file=%2Fsrc%2Fapp.php&line=42');
    });

    it('builds vscode URL', () => {
        const config: EditorConfig = {...defaultEditorConfig, editor: 'vscode'};
        expect(buildEditorUrl(config, '/src/app.php', 10)).toBe('vscode://file/%2Fsrc%2Fapp.php:10');
    });

    it('builds vscode-insiders URL', () => {
        const config: EditorConfig = {...defaultEditorConfig, editor: 'vscode-insiders'};
        expect(buildEditorUrl(config, '/src/app.php', 5)).toBe('vscode-insiders://file/%2Fsrc%2Fapp.php:5');
    });

    it('builds cursor URL', () => {
        const config: EditorConfig = {...defaultEditorConfig, editor: 'cursor'};
        expect(buildEditorUrl(config, '/src/app.php', 1)).toBe('cursor://file/%2Fsrc%2Fapp.php:1');
    });

    it('builds sublime URL', () => {
        const config: EditorConfig = {...defaultEditorConfig, editor: 'sublime'};
        expect(buildEditorUrl(config, '/src/app.php', 7)).toBe('subl://open?url=file://%2Fsrc%2Fapp.php&line=7');
    });

    it('builds zed URL', () => {
        const config: EditorConfig = {...defaultEditorConfig, editor: 'zed'};
        expect(buildEditorUrl(config, '/src/app.php', 3)).toBe('zed://file/%2Fsrc%2Fapp.php:3');
    });

    it('builds idea URL', () => {
        const config: EditorConfig = {...defaultEditorConfig, editor: 'idea'};
        expect(buildEditorUrl(config, '/src/app.php', 10)).toBe('idea://open?file=%2Fsrc%2Fapp.php&line=10');
    });

    it('builds webstorm URL', () => {
        const config: EditorConfig = {...defaultEditorConfig, editor: 'webstorm'};
        expect(buildEditorUrl(config, '/src/app.ts', 5)).toBe('webstorm://open?file=%2Fsrc%2Fapp.ts&line=5');
    });

    it('builds goland URL', () => {
        const config: EditorConfig = {...defaultEditorConfig, editor: 'goland'};
        expect(buildEditorUrl(config, '/src/main.go', 20)).toBe('goland://open?file=%2Fsrc%2Fmain.go&line=20');
    });

    it('builds pycharm URL', () => {
        const config: EditorConfig = {...defaultEditorConfig, editor: 'pycharm'};
        expect(buildEditorUrl(config, '/src/app.py', 8)).toBe('pycharm://open?file=%2Fsrc%2Fapp.py&line=8');
    });

    it('builds rubymine URL', () => {
        const config: EditorConfig = {...defaultEditorConfig, editor: 'rubymine'};
        expect(buildEditorUrl(config, '/src/app.rb', 3)).toBe('rubymine://open?file=%2Fsrc%2Fapp.rb&line=3');
    });

    it('builds rider URL', () => {
        const config: EditorConfig = {...defaultEditorConfig, editor: 'rider'};
        expect(buildEditorUrl(config, '/src/Program.cs', 12)).toBe('rider://open?file=%2Fsrc%2FProgram.cs&line=12');
    });

    it('builds clion URL', () => {
        const config: EditorConfig = {...defaultEditorConfig, editor: 'clion'};
        expect(buildEditorUrl(config, '/src/main.cpp', 1)).toBe('clion://open?file=%2Fsrc%2Fmain.cpp&line=1');
    });

    it('defaults line to 1 when not provided', () => {
        const config: EditorConfig = {...defaultEditorConfig, editor: 'phpstorm'};
        expect(buildEditorUrl(config, '/src/app.php')).toBe('phpstorm://open?file=%2Fsrc%2Fapp.php&line=1');
    });

    it('builds custom URL from template', () => {
        const config: EditorConfig = {
            editor: 'custom',
            customUrlTemplate: 'myeditor://open?file={file}&line={line}',
            pathMapping: {},
        };
        expect(buildEditorUrl(config, '/src/app.php', 99)).toBe('myeditor://open?file=%2Fsrc%2Fapp.php&line=99');
    });

    it('returns null for custom editor with empty template', () => {
        const config: EditorConfig = {editor: 'custom', customUrlTemplate: '', pathMapping: {}};
        expect(buildEditorUrl(config, '/src/app.php', 1)).toBeNull();
    });

    it('applies path mapping', () => {
        const config: EditorConfig = {
            editor: 'vscode',
            customUrlTemplate: '',
            pathMapping: {'/app': '/Users/dev/project'},
        };
        expect(buildEditorUrl(config, '/app/src/Controller.php', 15)).toBe(
            'vscode://file/%2FUsers%2Fdev%2Fproject%2Fsrc%2FController.php:15',
        );
    });

    it('does not apply mapping when path does not match', () => {
        const config: EditorConfig = {
            editor: 'vscode',
            customUrlTemplate: '',
            pathMapping: {'/app': '/Users/dev/project'},
        };
        expect(buildEditorUrl(config, '/other/file.php', 1)).toBe('vscode://file/%2Fother%2Ffile.php:1');
    });

    it('applies first matching path mapping', () => {
        const config: EditorConfig = {
            editor: 'phpstorm',
            customUrlTemplate: '',
            pathMapping: {'/app': '/local/app', '/app/vendor': '/local/vendor'},
        };
        // /app matches first
        expect(buildEditorUrl(config, '/app/vendor/lib.php', 1)).toBe(
            'phpstorm://open?file=%2Flocal%2Fapp%2Fvendor%2Flib.php&line=1',
        );
    });

    it('handles missing pathMapping on legacy persisted state', () => {
        // Legacy persist state may lack pathMapping — applyPathMapping must pass path through.
        const config = {editor: 'vscode', customUrlTemplate: ''} as unknown as EditorConfig;
        expect(buildEditorUrl(config, '/src/app.php', 1)).toBe('vscode://file/%2Fsrc%2Fapp.php:1');
    });

    it('skips empty remote keys in path mapping', () => {
        const config: EditorConfig = {
            editor: 'vscode',
            customUrlTemplate: '',
            pathMapping: {'': '/ignored', '/app': '/local'},
        };
        expect(buildEditorUrl(config, '/app/file.php', 1)).toBe('vscode://file/%2Flocal%2Ffile.php:1');
    });
});

describe('defaultEditorConfig', () => {
    it('has none as default editor', () => {
        expect(defaultEditorConfig.editor).toBe('none');
    });

    it('has empty custom template', () => {
        expect(defaultEditorConfig.customUrlTemplate).toBe('');
    });

    it('has empty path mapping', () => {
        expect(defaultEditorConfig.pathMapping).toEqual({});
    });
});

describe('editorPresetLabels', () => {
    it('has labels for all presets', () => {
        expect(Object.keys(editorPresetLabels)).toEqual([
            'none',
            'phpstorm',
            'idea',
            'webstorm',
            'goland',
            'pycharm',
            'rubymine',
            'rider',
            'clion',
            'vscode',
            'vscode-insiders',
            'cursor',
            'sublime',
            'zed',
            'custom',
        ]);
    });

    it('has human-readable labels', () => {
        expect(editorPresetLabels.phpstorm).toBe('PhpStorm');
        expect(editorPresetLabels.idea).toBe('IntelliJ IDEA');
        expect(editorPresetLabels.vscode).toBe('VS Code');
        expect(editorPresetLabels.none).toContain('None');
        expect(editorPresetLabels.custom).toContain('Custom');
    });
});
