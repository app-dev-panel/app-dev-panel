import {primitives} from '@app-dev-panel/sdk/Component/Theme/tokens';
import {Box, Icon, Modal, Typography} from '@mui/material';
import {styled} from '@mui/material/styles';
import {useCallback, useEffect, useRef, useState} from 'react';
import {useNavigate} from 'react-router-dom';

type PaletteItem = {
    icon: string;
    label: string;
    path?: string;
    shortcut?: string;
    action?: () => void;
    section: string;
};

type CommandPaletteProps = {open: boolean; onClose: () => void; extraItems?: PaletteItem[]};

const Backdrop = styled(Box)({
    position: 'fixed',
    inset: 0,
    backgroundColor: 'rgba(0,0,0,0.4)',
    backdropFilter: 'blur(4px)',
    display: 'flex',
    alignItems: 'flex-start',
    justifyContent: 'center',
    paddingTop: '12vh',
    zIndex: 1300,
});

const PaletteRoot = styled(Box)(({theme}) => ({
    width: 640,
    backgroundColor: theme.palette.background.paper,
    borderRadius: theme.shape.borderRadius * 1.5,
    boxShadow: '0 25px 50px -12px rgba(0,0,0,0.25)',
    overflow: 'hidden',
    animation: 'paletteFade 0.15s ease-out',
    '@keyframes paletteFade': {
        from: {opacity: 0, transform: 'scale(0.97) translateY(-4px)'},
        to: {opacity: 1, transform: 'scale(1) translateY(0)'},
    },
}));

const InputRow = styled(Box)(({theme}) => ({
    display: 'flex',
    alignItems: 'center',
    padding: theme.spacing(0, 2.5),
    height: 52,
    borderBottom: `1px solid ${theme.palette.divider}`,
}));

const SearchInput = styled('input')(({theme}) => ({
    flex: 1,
    border: 'none',
    outline: 'none',
    fontSize: '16px',
    fontFamily: primitives.fontFamily,
    backgroundColor: 'transparent',
    color: theme.palette.text.primary,
    '&::placeholder': {color: theme.palette.text.disabled},
}));

const Kbd = styled('kbd')(({theme}) => ({
    fontFamily: primitives.fontFamilyMono,
    fontSize: '10px',
    backgroundColor: theme.palette.background.default,
    padding: '2px 6px',
    borderRadius: 3,
    border: `1px solid ${theme.palette.divider}`,
    color: theme.palette.text.disabled,
}));

const PaletteBody = styled(Box)({maxHeight: 400, overflowY: 'auto', padding: '8px 0'});

const SectionLabel = styled(Typography)(({theme}) => ({
    fontSize: '11px',
    fontWeight: 600,
    textTransform: 'uppercase',
    letterSpacing: '0.5px',
    color: theme.palette.text.disabled,
    padding: theme.spacing(1, 2.5, 0.5),
}));

type ItemRowProps = {selected?: boolean};

const ItemRow = styled(Box, {shouldForwardProp: (p) => p !== 'selected'})<ItemRowProps>(({theme, selected}) => ({
    display: 'flex',
    alignItems: 'center',
    padding: theme.spacing(1, 2.5),
    gap: theme.spacing(1.25),
    cursor: 'pointer',
    transition: 'background 0.08s',
    position: 'relative',
    backgroundColor: selected ? theme.palette.primary.light : 'transparent',
    '&:hover': {backgroundColor: theme.palette.action.hover},
    ...(selected && {
        '&::before': {
            content: '""',
            width: 3,
            height: 20,
            backgroundColor: theme.palette.primary.main,
            borderRadius: '0 2px 2px 0',
            position: 'absolute',
            left: 0,
        },
    }),
}));

const ItemLabel = styled(Typography)({flex: 1, fontSize: '14px'});

const ItemShortcut = styled(Typography)(({theme}) => ({
    fontFamily: primitives.fontFamilyMono,
    fontSize: '12px',
    color: theme.palette.text.disabled,
}));

const defaultPages: PaletteItem[] = [
    {icon: 'home', label: 'Home', path: '/', section: 'Pages'},
    {icon: 'bug_report', label: 'Debug', path: '/debug', shortcut: '/debug', section: 'Pages'},
    {icon: 'grid_view', label: 'Debug > Overview', path: '/debug', section: 'Debug'},
    {icon: 'list', label: 'Debug > All Entries', path: '/debug/list', shortcut: '/entries', section: 'Debug'},
    {icon: 'settings', label: 'Inspector > Configuration', path: '/inspector/config', shortcut: '/config', section: 'Inspector'},
    {icon: 'bolt', label: 'Inspector > Events', path: '/inspector/events', shortcut: '/events', section: 'Inspector'},
    {icon: 'alt_route', label: 'Inspector > Routes', path: '/inspector/routes', shortcut: '/routes', section: 'Inspector'},
    {icon: 'science', label: 'Inspector > Tests', path: '/inspector/tests', shortcut: '/tests', section: 'Inspector'},
    {icon: 'analytics', label: 'Inspector > Analyse', path: '/inspector/analyse', shortcut: '/analyse', section: 'Inspector'},
    {icon: 'folder_open', label: 'Inspector > File Explorer', path: '/inspector/files', shortcut: '/files', section: 'Inspector'},
    {icon: 'translate', label: 'Inspector > Translations', path: '/inspector/translations', shortcut: '/i18n', section: 'Inspector'},
    {icon: 'terminal', label: 'Inspector > Commands', path: '/inspector/commands', shortcut: '/commands', section: 'Inspector'},
    {icon: 'storage', label: 'Inspector > Database', path: '/inspector/database', shortcut: '/database', section: 'Inspector'},
    {icon: 'cached', label: 'Inspector > Cache', path: '/inspector/cache', shortcut: '/cache', section: 'Inspector'},
    {icon: 'code', label: 'Inspector > Git', path: '/inspector/git', shortcut: '/git', section: 'Inspector'},
    {icon: 'info', label: 'Inspector > PHP Info', path: '/inspector/phpinfo', shortcut: '/phpinfo', section: 'Inspector'},
    {icon: 'inventory_2', label: 'Inspector > Composer', path: '/inspector/composer', shortcut: '/composer', section: 'Inspector'},
    {icon: 'speed', label: 'Inspector > Opcache', path: '/inspector/opcache', shortcut: '/opcache', section: 'Inspector'},
    {icon: 'build_circle', label: 'Gii', path: '/gii', shortcut: '/gii', section: 'Pages'},
    {icon: 'data_object', label: 'Open API', path: '/open-api', shortcut: '/openapi', section: 'Pages'},
    {icon: 'web', label: 'Frames', path: '/frames', shortcut: '/frames', section: 'Pages'},
];

export const CommandPalette = ({open, onClose, extraItems = []}: CommandPaletteProps) => {
    const [query, setQuery] = useState('');
    const [selectedIndex, setSelectedIndex] = useState(0);
    const inputRef = useRef<HTMLInputElement>(null);
    const navigate = useNavigate();

    const allItems = [...extraItems, ...defaultPages];

    const filtered = query
        ? allItems.filter(
              (item) =>
                  item.label.toLowerCase().includes(query.toLowerCase()) ||
                  (item.shortcut && item.shortcut.toLowerCase().includes(query.toLowerCase())),
          )
        : allItems;

    const sections = [...new Set(filtered.map((i) => i.section))];

    useEffect(() => {
        if (open) {
            setQuery('');
            setSelectedIndex(0);
            setTimeout(() => inputRef.current?.focus(), 50);
        }
    }, [open]);

    useEffect(() => {
        setSelectedIndex(0);
    }, [query]);

    const handleSelect = useCallback(
        (item: PaletteItem) => {
            onClose();
            if (item.action) {
                item.action();
            } else if (item.path) {
                navigate(item.path);
            }
        },
        [onClose, navigate],
    );

    const handleKeyDown = useCallback(
        (e: React.KeyboardEvent) => {
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                setSelectedIndex((prev) => Math.min(prev + 1, filtered.length - 1));
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                setSelectedIndex((prev) => Math.max(prev - 1, 0));
            } else if (e.key === 'Enter' && filtered[selectedIndex]) {
                e.preventDefault();
                handleSelect(filtered[selectedIndex]);
            } else if (e.key === 'Escape') {
                onClose();
            }
        },
        [filtered, selectedIndex, handleSelect, onClose],
    );

    let flatIndex = 0;

    return (
        <Modal open={open} onClose={onClose} slots={{backdrop: () => null}}>
            <Backdrop onClick={onClose}>
                <PaletteRoot onClick={(e) => e.stopPropagation()} onKeyDown={handleKeyDown}>
                    <InputRow>
                        <Icon sx={{color: 'text.disabled', fontSize: 20, mr: 1.25}}>search</Icon>
                        <SearchInput
                            ref={inputRef}
                            placeholder="Search pages, actions, entries..."
                            value={query}
                            onChange={(e) => setQuery(e.target.value)}
                        />
                        <Kbd>Esc</Kbd>
                    </InputRow>
                    <PaletteBody>
                        {sections.map((section) => {
                            const sectionItems = filtered.filter((i) => i.section === section);
                            return (
                                <Box key={section}>
                                    <SectionLabel>{section}</SectionLabel>
                                    {sectionItems.map((item) => {
                                        const idx = flatIndex++;
                                        return (
                                            <ItemRow
                                                key={item.label}
                                                selected={idx === selectedIndex}
                                                onClick={() => handleSelect(item)}
                                            >
                                                <Icon sx={{fontSize: 16, color: 'text.disabled'}}>{item.icon}</Icon>
                                                <ItemLabel>{item.label}</ItemLabel>
                                                {item.shortcut && <ItemShortcut>{item.shortcut}</ItemShortcut>}
                                            </ItemRow>
                                        );
                                    })}
                                </Box>
                            );
                        })}
                        {filtered.length === 0 && (
                            <Box sx={{textAlign: 'center', py: 4, color: 'text.disabled'}}>
                                <Typography variant="body2">No results found</Typography>
                            </Box>
                        )}
                    </PaletteBody>
                </PaletteRoot>
            </Backdrop>
        </Modal>
    );
};
