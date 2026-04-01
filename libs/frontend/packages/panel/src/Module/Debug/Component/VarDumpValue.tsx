import {CodeHighlight} from '@app-dev-panel/sdk/Component/CodeHighlight';
import {Box} from '@mui/material';
import {styled} from '@mui/material/styles';
import {useCallback, useState} from 'react';

// ---------------------------------------------------------------------------
// Types
// ---------------------------------------------------------------------------

type VarDumpValueProps = {value: unknown; depth?: number; defaultExpanded?: boolean};

// ---------------------------------------------------------------------------
// Styled primitives (theme-aware, no hardcoded colors)
// ---------------------------------------------------------------------------

const Mono = styled('span')(({theme}) => ({
    fontFamily: "'JetBrains Mono', monospace",
    fontSize: '13px',
    lineHeight: 1.6,
    color: theme.palette.text.primary,
}));

const Keyword = styled('span')(({theme}) => ({
    fontFamily: "'JetBrains Mono', monospace",
    fontSize: '13px',
    fontWeight: 600,
    color: theme.palette.text.secondary,
}));

const StringValue = styled('span')(({theme}) => ({
    fontFamily: "'JetBrains Mono', monospace",
    fontSize: '13px',
    color: theme.palette.success.main,
}));

const NumberValue = styled('span')(({theme}) => ({
    fontFamily: "'JetBrains Mono', monospace",
    fontSize: '13px',
    color: theme.palette.primary.main,
}));

const BoolValue = styled('span')(({theme}) => ({
    fontFamily: "'JetBrains Mono', monospace",
    fontSize: '13px',
    fontWeight: 600,
    color: theme.palette.warning.main,
}));

const NullValue = styled('span')(({theme}) => ({
    fontFamily: "'JetBrains Mono', monospace",
    fontSize: '13px',
    fontStyle: 'italic',
    color: theme.palette.text.disabled,
}));

const KeyName = styled('span')(({theme}) => ({
    fontFamily: "'JetBrains Mono', monospace",
    fontSize: '13px',
    fontWeight: 600,
    color: theme.palette.text.primary,
}));

const ClassName = styled('span')(({theme}) => ({
    fontFamily: "'JetBrains Mono', monospace",
    fontSize: '13px',
    fontWeight: 600,
    fontStyle: 'italic',
    color: theme.palette.warning.main,
}));

const ToggleArrow = styled('span')(({theme}) => ({
    fontFamily: "'JetBrains Mono', monospace",
    fontSize: '13px',
    cursor: 'pointer',
    userSelect: 'none',
    color: theme.palette.text.secondary,
    '&:hover': {color: theme.palette.primary.main},
}));

const Annotation = styled('span')(({theme}) => ({
    fontFamily: "'JetBrains Mono', monospace",
    fontSize: '11px',
    color: theme.palette.text.disabled,
    marginLeft: '4px',
}));

const IndentBlock = styled('div')({paddingLeft: '20px'});

const ObjectRef = styled('span')(({theme}) => ({
    fontFamily: "'JetBrains Mono', monospace",
    fontSize: '13px',
    fontStyle: 'italic',
    color: theme.palette.info?.main ?? theme.palette.primary.main,
}));

const ResourceValue = styled('span')(({theme}) => ({
    fontFamily: "'JetBrains Mono', monospace",
    fontSize: '13px',
    fontStyle: 'italic',
    color: theme.palette.warning.main,
}));

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

const OBJECT_PATTERN = /^(.+)#(\d+)$/;
const OBJECT_REF_PATTERN = /^object@(.+#\d+)$/;
const TRUNCATED_ARRAY_PATTERN = /^array \((\d+) items?\) \[\.{3}]$/;
const TRUNCATED_OBJECT_PATTERN = /^(.+#\d+) \(\.\.\.\)$/;
const RESOURCE_PATTERN = /^\{(.+) resource\}$|^\{closed resource\}$|^\{resource\}$|^\{stateless object\}$/;
const CLOSURE_PATTERN = /^(static )?(function |fn )\(.*\).*/s;

function isObjectKey(key: string): {className: string; objectId: string} | null {
    const match = key.match(OBJECT_PATTERN);
    if (!match) return null;
    return {className: match[1], objectId: match[2]};
}

function isObjectReference(value: string): string | null {
    const match = value.match(OBJECT_REF_PATTERN);
    return match ? match[1] : null;
}

function isTruncatedArray(value: string): number | null {
    const match = value.match(TRUNCATED_ARRAY_PATTERN);
    return match ? parseInt(match[1], 10) : null;
}

function isTruncatedObject(value: string): string | null {
    const match = value.match(TRUNCATED_OBJECT_PATTERN);
    return match ? match[1] : null;
}

function isResource(value: string): boolean {
    return RESOURCE_PATTERN.test(value);
}

function isClosure(value: string): boolean {
    return CLOSURE_PATTERN.test(value);
}

function isAssociativeArray(obj: Record<string, unknown>): boolean {
    const keys = Object.keys(obj);
    // If all keys are sequential integers starting from 0, treat as indexed array
    return !keys.every((key, index) => key === String(index));
}

function hasObjectWrapper(
    obj: Record<string, unknown>,
): {className: string; objectId: string; properties: Record<string, unknown>} | null {
    const keys = Object.keys(obj);
    if (keys.length !== 1) return null;
    const info = isObjectKey(keys[0]);
    if (!info) return null;
    const inner = obj[keys[0]];
    if (typeof inner === 'object' && inner !== null && !Array.isArray(inner)) {
        return {...info, properties: inner as Record<string, unknown>};
    }
    // Handle stateless object string
    if (typeof inner === 'string') {
        return {...info, properties: {}};
    }
    return null;
}

function getPropertyVisibility(key: string): {visibility: 'public' | 'private' | 'protected'; name: string} | null {
    if (key.startsWith('public $')) return {visibility: 'public', name: key.slice(8)};
    if (key.startsWith('private $')) return {visibility: 'private', name: key.slice(9)};
    if (key.startsWith('protected $')) return {visibility: 'protected', name: key.slice(11)};
    return null;
}

// ---------------------------------------------------------------------------
// Collapsible wrapper
// ---------------------------------------------------------------------------

type CollapsibleProps = {header: React.ReactNode; children: React.ReactNode; defaultExpanded: boolean};

const Collapsible = ({header, children, defaultExpanded}: CollapsibleProps) => {
    const [expanded, setExpanded] = useState(defaultExpanded);
    const toggle = useCallback(() => setExpanded((prev) => !prev), []);

    return (
        <span>
            <ToggleArrow onClick={toggle} role="button" aria-expanded={expanded}>
                {expanded ? '\u25BC' : '\u25B6'}
            </ToggleArrow>{' '}
            {header}
            {expanded ? (
                <>
                    <IndentBlock>{children}</IndentBlock>
                </>
            ) : (
                <Mono> {'\u2026'}</Mono>
            )}
        </span>
    );
};

// ---------------------------------------------------------------------------
// Main component
// ---------------------------------------------------------------------------

export const VarDumpValue = ({value, depth = 0, defaultExpanded = true}: VarDumpValueProps) => {
    const shouldExpand = depth < 2 ? defaultExpanded : false;

    // null
    if (value === null || value === undefined) {
        return <NullValue>null</NullValue>;
    }

    // boolean
    if (typeof value === 'boolean') {
        return <BoolValue>{value ? 'true' : 'false'}</BoolValue>;
    }

    // number
    if (typeof value === 'number') {
        return (
            <span>
                <NumberValue>{String(value)}</NumberValue>
                <Annotation>{Number.isInteger(value) ? 'int' : 'float'}</Annotation>
            </span>
        );
    }

    // string
    if (typeof value === 'string') {
        // Object reference: "object@ClassName#id"
        const objRef = isObjectReference(value);
        if (objRef) {
            return (
                <ObjectRef>
                    {objRef} {'{...}'}
                </ObjectRef>
            );
        }

        // Truncated array: "array (5 items) [...]"
        const truncCount = isTruncatedArray(value);
        if (truncCount !== null) {
            return (
                <Mono>
                    <Keyword>array</Keyword>:<NumberValue>{truncCount}</NumberValue> [{'\u2026'}]
                </Mono>
            );
        }

        // Truncated object: "ClassName#id (...)"
        const truncObj = isTruncatedObject(value);
        if (truncObj) {
            return (
                <Mono>
                    <ClassName>{truncObj}</ClassName> {'{...}'}
                </Mono>
            );
        }

        // Resource
        if (isResource(value)) {
            return <ResourceValue>{value}</ResourceValue>;
        }

        // Closure / function
        if (isClosure(value)) {
            return (
                <Box sx={{display: 'inline-block'}}>
                    <CodeHighlight language="php" code={value} showLineNumbers={false} fontSize={10} />
                </Box>
            );
        }

        // Regular string
        return (
            <span>
                <StringValue>&quot;{value}&quot;</StringValue>
                <Annotation>({value.length})</Annotation>
            </span>
        );
    }

    // array (JSON array or plain object)
    if (typeof value === 'object') {
        // Actual JS array (indexed PHP array)
        if (Array.isArray(value)) {
            if (value.length === 0) {
                return (
                    <Mono>
                        <Keyword>array</Keyword>:0 []
                    </Mono>
                );
            }

            return (
                <Collapsible
                    defaultExpanded={shouldExpand}
                    header={
                        <Mono>
                            <Keyword>array</Keyword>:<NumberValue>{value.length}</NumberValue> [
                        </Mono>
                    }
                >
                    {value.map((item, index) => (
                        <div key={index}>
                            <NumberValue>{index}</NumberValue>
                            <Mono> =&gt; </Mono>
                            <VarDumpValue value={item} depth={depth + 1} defaultExpanded={shouldExpand} />
                        </div>
                    ))}
                    <Mono>]</Mono>
                </Collapsible>
            );
        }

        const obj = value as Record<string, unknown>;
        const keys = Object.keys(obj);

        // Empty object
        if (keys.length === 0) {
            return (
                <Mono>
                    <Keyword>array</Keyword>:0 {'{}'}
                </Mono>
            );
        }

        // Object with class wrapper: {"ClassName#id": {properties}}
        const wrapper = hasObjectWrapper(obj);
        if (wrapper) {
            const propKeys = Object.keys(wrapper.properties);
            if (propKeys.length === 0) {
                return (
                    <Mono>
                        <ClassName>{wrapper.className}</ClassName>
                        <Annotation>#{wrapper.objectId}</Annotation>
                        {' {}'}
                        {typeof obj[keys[0]] === 'string' ? <Annotation> {String(obj[keys[0]])}</Annotation> : null}
                    </Mono>
                );
            }

            return (
                <Collapsible
                    defaultExpanded={shouldExpand}
                    header={
                        <Mono>
                            <ClassName>{wrapper.className}</ClassName>
                            <Annotation>#{wrapper.objectId}</Annotation>
                            {' {'}
                        </Mono>
                    }
                >
                    {propKeys.map((propKey) => {
                        const propInfo = getPropertyVisibility(propKey);
                        return (
                            <div key={propKey}>
                                {propInfo ? (
                                    <>
                                        <Keyword>{propInfo.visibility}</Keyword> <KeyName>${propInfo.name}</KeyName>
                                    </>
                                ) : (
                                    <KeyName>{propKey}</KeyName>
                                )}
                                <Mono>: </Mono>
                                <VarDumpValue
                                    value={wrapper.properties[propKey]}
                                    depth={depth + 1}
                                    defaultExpanded={shouldExpand}
                                />
                            </div>
                        );
                    })}
                    <Mono>{'}'}</Mono>
                </Collapsible>
            );
        }

        // Associative array (object keys that aren't sequential integers)
        const isAssoc = isAssociativeArray(obj);

        return (
            <Collapsible
                defaultExpanded={shouldExpand}
                header={
                    <Mono>
                        <Keyword>array</Keyword>:<NumberValue>{keys.length}</NumberValue> {isAssoc ? '{' : '['}
                    </Mono>
                }
            >
                {keys.map((key) => {
                    const propInfo = getPropertyVisibility(key);
                    return (
                        <div key={key}>
                            {propInfo ? (
                                <>
                                    <Keyword>{propInfo.visibility}</Keyword> <KeyName>${propInfo.name}</KeyName>
                                </>
                            ) : isAssoc ? (
                                <KeyName>{key}</KeyName>
                            ) : (
                                <NumberValue>{key}</NumberValue>
                            )}
                            <Mono>{isAssoc ? ': ' : ' => '}</Mono>
                            <VarDumpValue value={obj[key]} depth={depth + 1} defaultExpanded={shouldExpand} />
                        </div>
                    );
                })}
                <Mono>{isAssoc ? '}' : ']'}</Mono>
            </Collapsible>
        );
    }

    // Fallback
    return <Mono>{String(value)}</Mono>;
};
