import {
    Css,
    DataObject,
    FilePresent,
    Folder,
    Html,
    Http,
    Image,
    Javascript,
    Link,
    Php,
    PictureAsPdf,
    Terminal,
    TextSnippet,
} from '@mui/icons-material';
import {InspectorFile} from '@yiisoft/yii-dev-panel/Module/Inspector/API/Inspector';

function findByExtension(extension: string) {
    switch (extension) {
        case 'php':
            return Php;
        case 'html':
            return Html;
        case 'http':
            return Http;
        case 'pdf':
            return PictureAsPdf;
        case 'yaml':
        case 'yml':
        case 'toml':
        case 'ini':
        case 'json':
        case 'xml':
            return DataObject;
        case 'bash':
        case 'sh':
        case 'shell':
        case 'bat':
            return Terminal;
        case 'jpg':
        case 'jpeg':
        case 'png':
        case 'webp':
        case 'heic':
        case 'ico':
        case 'icon':
        case 'svg':
            return Image;
        case 'js':
        case 'jsx':
        case 'ts':
        case 'tsx':
            return Javascript;
        case 'css':
        case 'scss':
        case 'sass':
        case 'less':
            return Css;
        case 'txt':
        case 'htaccess':
        case 'md':
            return TextSnippet;
    }
    return null;
}

export function fileExtensionIcon(row: InspectorFile) {
    if (row.type == 'dir') {
        return Folder;
    }
    if (row.type == 'link') {
        return Link;
    }
    const icon = findByExtension(row.extension);

    if (icon) {
        return icon;
    }
    const dotsNumber = row.baseName.match(/\./g)?.length || 0;
    if (dotsNumber > 1) {
        const strings = row.baseName.split('.');
        const secondExtension = strings.at(-2);
        if (secondExtension) {
            const icon = findByExtension(secondExtension);
            if (icon) {
                return icon;
            }
        }
    }

    return FilePresent;
}
