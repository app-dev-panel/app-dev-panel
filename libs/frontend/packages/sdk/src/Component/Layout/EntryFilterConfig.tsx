import {primitives} from '@app-dev-panel/sdk/Component/Theme/tokens';
import {Box, Icon, IconButton, MenuItem, Popover, Select, TextField, Typography} from '@mui/material';
import {styled} from '@mui/material/styles';

// ---------------------------------------------------------------------------
// Types
// ---------------------------------------------------------------------------

export type FilterField = 'url' | 'status' | 'type';
export type FilterOperator = 'contains' | 'starts_with' | 'ends_with' | 'greater_than' | 'equals';

export type FilterCondition = {
    id: string;
    field: FilterField;
    operator: FilterOperator;
    value: string;
};

export type EntryFilterState = {
    enabled: boolean;
    conditions: FilterCondition[];
};

// ---------------------------------------------------------------------------
// Constants
// ---------------------------------------------------------------------------

const STORAGE_KEY = 'adp:entry-filter';

const fieldOptions: Array<{value: FilterField; label: string}> = [
    {value: 'url', label: 'URL'},
    {value: 'status', label: 'Status'},
    {value: 'type', label: 'Type'},
];

const operatorOptions: Array<{value: FilterOperator; label: string}> = [
    {value: 'contains', label: 'contains'},
    {value: 'starts_with', label: 'starts with'},
    {value: 'ends_with', label: 'ends with'},
    {value: 'greater_than', label: 'greater than'},
    {value: 'equals', label: 'equals'},
];

// ---------------------------------------------------------------------------
// Persistence
// ---------------------------------------------------------------------------

export function loadFilterState(): EntryFilterState {
    try {
        const raw = localStorage.getItem(STORAGE_KEY);
        if (raw) return JSON.parse(raw) as EntryFilterState;
    } catch {
        /* ignore */
    }
    return {enabled: false, conditions: []};
}

export function saveFilterState(state: EntryFilterState): void {
    try {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
    } catch {
        /* ignore */
    }
}

// ---------------------------------------------------------------------------
// Styled components
// ---------------------------------------------------------------------------

const Header = styled(Box)(({theme}) => ({
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'space-between',
    padding: theme.spacing(1.5, 2),
    borderBottom: `1px solid ${theme.palette.divider}`,
}));

const ConditionRow = styled(Box)(({theme}) => ({
    display: 'flex',
    alignItems: 'center',
    gap: theme.spacing(1),
    padding: theme.spacing(1, 2),
    borderBottom: `1px solid ${theme.palette.divider}`,
    '&:last-of-type': {borderBottom: 'none'},
}));

const AddRow = styled(Box)(({theme}) => ({
    display: 'flex',
    justifyContent: 'flex-end',
    padding: theme.spacing(1, 2),
    borderTop: `1px solid ${theme.palette.divider}`,
}));

const SmallSelect = styled(Select)(({theme}) => ({
    fontSize: '12px',
    fontFamily: primitives.fontFamilyMono,
    '& .MuiSelect-select': {padding: theme.spacing(0.5, 1), paddingRight: '24px !important'},
    '& .MuiOutlinedInput-notchedOutline': {borderColor: theme.palette.divider},
}));

const SmallTextField = styled(TextField)(({theme}) => ({
    '& .MuiInputBase-input': {
        fontSize: '12px',
        fontFamily: primitives.fontFamilyMono,
        padding: theme.spacing(0.5, 1),
    },
    '& .MuiOutlinedInput-notchedOutline': {borderColor: theme.palette.divider},
}));

const AddButton = styled('button')(({theme}) => ({
    border: 'none',
    background: 'none',
    cursor: 'pointer',
    fontSize: '12px',
    fontWeight: 600,
    color: theme.palette.primary.main,
    padding: theme.spacing(0.5, 1.5),
    borderRadius: theme.shape.borderRadius / 2,
    display: 'flex',
    alignItems: 'center',
    gap: theme.spacing(0.5),
    '&:hover': {backgroundColor: theme.palette.action.hover},
}));

// ---------------------------------------------------------------------------
// Component
// ---------------------------------------------------------------------------

type EntryFilterConfigProps = {
    anchorEl: HTMLElement | null;
    open: boolean;
    onClose: () => void;
    filterState: EntryFilterState;
    onChange: (state: EntryFilterState) => void;
};

let nextId = 1;
function generateId(): string {
    return `cond_${nextId++}_${Date.now()}`;
}

export const EntryFilterConfig = ({anchorEl, open, onClose, filterState, onChange}: EntryFilterConfigProps) => {
    const {conditions} = filterState;

    const updateCondition = (id: string, updates: Partial<FilterCondition>) => {
        const next = conditions.map((c) => (c.id === id ? {...c, ...updates} : c));
        const newState = {...filterState, conditions: next};
        onChange(newState);
        saveFilterState(newState);
    };

    const removeCondition = (id: string) => {
        const next = conditions.filter((c) => c.id !== id);
        const newState = {...filterState, conditions: next};
        onChange(newState);
        saveFilterState(newState);
    };

    const addCondition = () => {
        const newCondition: FilterCondition = {id: generateId(), field: 'url', operator: 'contains', value: ''};
        const newState = {...filterState, conditions: [...conditions, newCondition]};
        onChange(newState);
        saveFilterState(newState);
    };

    return (
        <Popover
            open={open}
            anchorEl={anchorEl}
            onClose={onClose}
            anchorOrigin={{vertical: 'bottom', horizontal: 'right'}}
            transformOrigin={{vertical: 'top', horizontal: 'right'}}
            slotProps={{paper: {sx: {width: 480, mt: 0.5, borderRadius: 1.5}}}}
        >
            <Header>
                <Typography sx={{fontSize: '13px', fontWeight: 600}}>Filter Conditions</Typography>
                <Typography sx={{fontSize: '11px', color: 'text.disabled'}}>
                    {conditions.length} condition{conditions.length !== 1 ? 's' : ''}
                </Typography>
            </Header>

            {conditions.length === 0 && (
                <Box sx={{textAlign: 'center', py: 3, color: 'text.disabled'}}>
                    <Typography variant="body2">No filter conditions</Typography>
                </Box>
            )}

            {conditions.map((condition) => (
                <ConditionRow key={condition.id}>
                    <SmallSelect
                        size="small"
                        value={condition.field}
                        onChange={(e) => updateCondition(condition.id, {field: e.target.value as FilterField})}
                        sx={{minWidth: 80}}
                    >
                        {fieldOptions.map((opt) => (
                            <MenuItem key={opt.value} value={opt.value} sx={{fontSize: '12px'}}>
                                {opt.label}
                            </MenuItem>
                        ))}
                    </SmallSelect>

                    <SmallSelect
                        size="small"
                        value={condition.operator}
                        onChange={(e) => updateCondition(condition.id, {operator: e.target.value as FilterOperator})}
                        sx={{minWidth: 110}}
                    >
                        {operatorOptions.map((opt) => (
                            <MenuItem key={opt.value} value={opt.value} sx={{fontSize: '12px'}}>
                                {opt.label}
                            </MenuItem>
                        ))}
                    </SmallSelect>

                    <SmallTextField
                        size="small"
                        placeholder={condition.field === 'type' ? 'http / cli' : 'value...'}
                        value={condition.value}
                        onChange={(e) => updateCondition(condition.id, {value: e.target.value})}
                        sx={{flex: 1}}
                    />

                    <IconButton size="small" onClick={() => removeCondition(condition.id)} sx={{flexShrink: 0}}>
                        <Icon sx={{fontSize: 16}}>close</Icon>
                    </IconButton>
                </ConditionRow>
            ))}

            <AddRow>
                <AddButton onClick={addCondition}>
                    <Icon sx={{fontSize: 14}}>add</Icon>
                    Add condition
                </AddButton>
            </AddRow>
        </Popover>
    );
};
