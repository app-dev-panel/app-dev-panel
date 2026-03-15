---
name: frontend-designer
description: Design and implement frontend UI components, pages, and modules for the React/TypeScript debug panel. Follows project conventions — MUI 5, Redux Toolkit, ModuleInterface pattern, Prettier + ESLint rules.
argument-hint: "[component, page, or module to design]"
allowed-tools: Read, Write, Edit, Grep, Glob, Bash, Agent
---

# Frontend Designer

Design and implement: $ARGUMENTS

## Tech Stack

- React 18.3+, TypeScript 5.5+ (strict mode)
- Material-UI (MUI) 5 — components and `sx` prop for styling
- Redux Toolkit 1.9+ with RTK Query — state and API calls
- React Router 6 — navigation
- React Hook Form + Yup — forms and validation
- Vite 5.4+ — build tool

## Rules

1. **Use `type` not `interface`** for TypeScript definitions (`consistent-type-definitions: "type"`).
2. **Functional components only**. No class components.
3. **MUI components** for all UI elements — Button, Box, Typography, DataGrid, etc. No custom CSS files.
4. **Styling via `sx` prop** or `styled()` API. Use theme-aware values (`theme.spacing`, `theme.palette`, `theme.transitions`).
5. **Redux Toolkit** for state. Use `createSlice` for local state, RTK Query for API calls.
6. **RTK Query** for all API communication. Define endpoints in the appropriate API slice under `yii-dev-panel-sdk/src/API/`.
7. **Dynamic base URL**: Use `createBaseQuery(prefix)` — reads `application.baseUrl` from Redux state.
8. **Module system**: New pages belong to a module implementing `ModuleInterface` (routes, reducers, middlewares, standaloneModule).
9. **Shared components** go in `yii-dev-panel-sdk/src/Component/`. Page-specific components stay in the module directory.
10. **Prettier rules**: Single quotes, trailing commas, 120 char width, 4-space indent, `objectWrap: "collapse"`. Run `npm run format` after changes.
11. **ESLint rules**: `@typescript-eslint/recommended` + Prettier integration. Run `npm run lint:fix` after changes.
12. **Path aliases**: Use `@yiisoft/yii-dev-panel/*`, `@yiisoft/yii-dev-panel-sdk/*`, `@yiisoft/yii-dev-toolbar/*` — never relative paths across packages.
13. **No emojis** in code or UI unless explicitly requested.

## Before Implementing

1. Read existing components in the target module — match style, patterns, imports.
2. Read shared SDK components in `yii-dev-panel-sdk/src/Component/` — reuse before creating new ones.
3. Read the module's `ModuleInterface` export — understand route structure and reducers.
4. For new API endpoints — read existing RTK Query API slices for the pattern.

## File Placement

| What | Where |
|------|-------|
| Page component | `packages/yii-dev-panel/src/Module/<Module>/Pages/` |
| Module-specific component | `packages/yii-dev-panel/src/Module/<Module>/Component/` |
| Shared/reusable component | `packages/yii-dev-panel-sdk/src/Component/` |
| RTK Query API slice | `packages/yii-dev-panel-sdk/src/API/<Domain>/` |
| Redux slice | `packages/yii-dev-panel-sdk/src/API/<Domain>/` |
| Type definitions | `packages/yii-dev-panel-sdk/src/Types/` |
| Helper functions | `packages/yii-dev-panel-sdk/src/Helper/` |
| Module registration | `packages/yii-dev-panel/src/modules.ts` |
| Route registration | Module's `index.ts` exporting `ModuleInterface` |
| Toolbar component | `packages/yii-dev-toolbar/src/Module/Toolbar/` |

## Component Template

```tsx
import {Box, Typography} from '@mui/material';

type MyComponentProps = {
    title: string;
    onAction: () => void;
};

export const MyComponent = ({title, onAction}: MyComponentProps) => {
    return (
        <Box sx={{p: 2}}>
            <Typography variant="h6">{title}</Typography>
        </Box>
    );
};
```

## RTK Query Endpoint Template

```tsx
import {createApi} from '@reduxjs/toolkit/query/react';
import {createBaseQuery} from '../createBaseQuery';

export const myApi = createApi({
    reducerPath: 'api.my',
    baseQuery: createBaseQuery('/debug/api/my/'),
    endpoints: (builder) => ({
        getItems: builder.query<ItemResponse, string>({
            query: (id) => `items/${id}`,
        }),
    }),
});

export const {useGetItemsQuery} = myApi;
```

## Module Template

```tsx
import {RouteObject} from 'react-router-dom';
import {Middleware, Reducer} from '@reduxjs/toolkit';

type ModuleInterface = {
    routes: RouteObject[];
    reducers: Record<string, Reducer>;
    middlewares: Middleware[];
    standaloneModule: boolean;
};

export const MyModule: ModuleInterface = {
    routes: [
        {path: 'my', element: <MyPage />},
    ],
    reducers: {},
    middlewares: [],
    standaloneModule: false,
};
```

## After Implementing

1. Run `npm run format` in `libs/yii-dev-panel/` — fix formatting.
2. Run `npm run lint:fix` in `libs/yii-dev-panel/` — fix lint issues.
3. Run `npm run build` in `libs/yii-dev-panel/` — verify build succeeds.
4. Verify new module is registered in `modules.ts` (if applicable).
5. Verify new reducers/middlewares are picked up by `store.ts` (if applicable).

## Anti-Patterns

- No inline styles — use MUI `sx` prop or `styled()`.
- No `any` types — use proper TypeScript types.
- No direct `fetch()` calls — use RTK Query.
- No `useState` for server data — use RTK Query hooks.
- No CSS/SCSS files — MUI handles all styling.
- No relative imports across packages — use path aliases.
- No `interface` keyword — use `type`.
