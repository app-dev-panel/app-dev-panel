import {Box, IconButton, Tooltip, useTheme} from '@mui/material';
import Icon from '@mui/material/Icon';
import React, {useCallback, useMemo, useState} from 'react';
import {Prism} from 'react-syntax-highlighter';
import {darcula} from 'react-syntax-highlighter/dist/esm/styles/prism';
import {format as formatSql} from 'sql-formatter';

type SqlHighlightProps = {
    /** Raw SQL string */
    sql: string;
    /** Show formatted (pretty-printed) SQL. Default: true */
    formatted?: boolean;
    /** Allow toggling between formatted and raw view. Default: true */
    allowToggle?: boolean;
    /** Show line numbers for formatted view. Default: false */
    showLineNumbers?: boolean;
    /** Font size in px. Default: 12 */
    fontSize?: number;
    /** SQL dialect for formatting. Default: 'sql' */
    dialect?: 'sql' | 'mysql' | 'mariadb' | 'postgresql' | 'sqlite' | 'bigquery' | 'plsql' | 'transactsql';
    /** Inline mode — single-line display without formatting, suitable for table cells. Default: false */
    inline?: boolean;
};

const dialectMap: Record<
    string,
    'sql' | 'mysql' | 'mariadb' | 'postgresql' | 'sqlite' | 'bigquery' | 'plsql' | 'transactsql'
> = {
    sql: 'sql',
    mysql: 'mysql',
    mariadb: 'mariadb',
    postgresql: 'postgresql',
    sqlite: 'sqlite',
    bigquery: 'bigquery',
    plsql: 'plsql',
    transactsql: 'transactsql',
};

function tryFormatSql(sql: string, dialect: string): string {
    try {
        return formatSql(sql, {language: dialectMap[dialect] ?? 'sql', tabWidth: 2, keywordCase: 'upper'});
    } catch {
        return sql;
    }
}

export const SqlHighlight = React.memo(
    ({
        sql,
        formatted = true,
        allowToggle = true,
        showLineNumbers = false,
        fontSize = 12,
        dialect = 'sql',
        inline = false,
    }: SqlHighlightProps) => {
        const theme = useTheme();
        const [isFormatted, setIsFormatted] = useState(formatted);

        const formattedSql = useMemo(() => tryFormatSql(sql, dialect), [sql, dialect]);
        const displaySql = isFormatted && !inline ? formattedSql : sql;

        const isMultiLine = formattedSql !== sql && formattedSql.includes('\n');

        const handleToggle = useCallback(() => {
            setIsFormatted((prev) => !prev);
        }, []);

        if (inline) {
            return (
                <Prism
                    style={theme.palette.mode === 'dark' ? darcula : undefined}
                    language="sql"
                    showLineNumbers={false}
                    customStyle={{
                        fontSize: `${fontSize}px`,
                        margin: 0,
                        padding: '0 4px',
                        background: 'transparent',
                        display: 'inline',
                        whiteSpace: 'pre-wrap',
                        wordBreak: 'break-word',
                        lineHeight: 1.6,
                    }}
                    codeTagProps={{style: {fontFamily: "'JetBrains Mono', monospace"}}}
                >
                    {sql}
                </Prism>
            );
        }

        return (
            <Box sx={{position: 'relative'}}>
                {allowToggle && isMultiLine && (
                    <Box sx={{position: 'absolute', top: 4, right: 4, zIndex: 1, display: 'flex', gap: 0.5}}>
                        <Tooltip title={isFormatted ? 'Show raw SQL' : 'Format SQL'} placement="top">
                            <IconButton size="small" onClick={handleToggle} sx={{padding: '2px'}}>
                                <Icon sx={{fontSize: 14, color: 'text.disabled'}}>
                                    {isFormatted ? 'code_off' : 'code'}
                                </Icon>
                            </IconButton>
                        </Tooltip>
                    </Box>
                )}
                <Prism
                    style={theme.palette.mode === 'dark' ? darcula : undefined}
                    language="sql"
                    showLineNumbers={showLineNumbers && isFormatted && isMultiLine}
                    wrapLines
                    customStyle={{
                        fontSize: `${fontSize}px`,
                        margin: 0,
                        padding: '8px 12px',
                        borderRadius: 4,
                        lineHeight: 1.6,
                    }}
                    codeTagProps={{style: {fontFamily: "'JetBrains Mono', monospace"}}}
                >
                    {displaySql}
                </Prism>
            </Box>
        );
    },
);
