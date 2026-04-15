export type EditorPreset =
    | 'none'
    | 'phpstorm'
    | 'idea'
    | 'webstorm'
    | 'goland'
    | 'pycharm'
    | 'rubymine'
    | 'rider'
    | 'clion'
    | 'vscode'
    | 'vscode-insiders'
    | 'cursor'
    | 'sublime'
    | 'zed'
    | 'custom';

export type EditorConfig = {editor: EditorPreset; customUrlTemplate: string; pathMapping: Record<string, string>};

export const defaultEditorConfig: EditorConfig = {editor: 'none', customUrlTemplate: '', pathMapping: {}};

const editorTemplates: Record<Exclude<EditorPreset, 'none' | 'custom'>, string> = {
    phpstorm: 'phpstorm://open?file={file}&line={line}',
    idea: 'idea://open?file={file}&line={line}',
    webstorm: 'webstorm://open?file={file}&line={line}',
    goland: 'goland://open?file={file}&line={line}',
    pycharm: 'pycharm://open?file={file}&line={line}',
    rubymine: 'rubymine://open?file={file}&line={line}',
    rider: 'rider://open?file={file}&line={line}',
    clion: 'clion://open?file={file}&line={line}',
    vscode: 'vscode://file/{file}:{line}',
    'vscode-insiders': 'vscode-insiders://file/{file}:{line}',
    cursor: 'cursor://file/{file}:{line}',
    sublime: 'subl://open?url=file://{file}&line={line}',
    zed: 'zed://file/{file}:{line}',
};

export const editorPresetLabels: Record<EditorPreset, string> = {
    none: 'None (File Explorer only)',
    phpstorm: 'PhpStorm',
    idea: 'IntelliJ IDEA',
    webstorm: 'WebStorm',
    goland: 'GoLand',
    pycharm: 'PyCharm',
    rubymine: 'RubyMine',
    rider: 'Rider',
    clion: 'CLion',
    vscode: 'VS Code',
    'vscode-insiders': 'VS Code Insiders',
    cursor: 'Cursor',
    sublime: 'Sublime Text',
    zed: 'Zed',
    custom: 'Custom URL template',
};

function applyPathMapping(filePath: string, mapping: Record<string, string> | undefined): string {
    if (!mapping) return filePath;
    for (const [remote, local] of Object.entries(mapping)) {
        if (remote === '') continue;
        if (filePath.startsWith(remote)) {
            return local + filePath.slice(remote.length);
        }
    }
    return filePath;
}

export function buildEditorUrl(config: EditorConfig, filePath: string, line?: number): string | null {
    if (config.editor === 'none') return null;

    const template = config.editor === 'custom' ? config.customUrlTemplate : editorTemplates[config.editor];
    if (!template) return null;

    const mappedPath = applyPathMapping(filePath, config.pathMapping);
    return template.replace('{file}', encodeURIComponent(mappedPath)).replace('{line}', String(line ?? 1));
}
