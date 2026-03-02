# Vercel React Best Practices

## Core Optimization Rules

### 1. Eliminating Waterfalls (CRITICAL)
- **`async-defer-await`**: Move `await` into branches where data is actually used. Don't block the whole component if data is only needed conditionally.
- **`async-parallel`**: Use `Promise.all()` for independent operations.
- **`async-dependencies`**: Start promises early, await them late.
- **`async-suspense-boundaries`**: Use Suspense to stream content while data loads.

### 2. Bundle Size Optimization (CRITICAL)
- **`bundle-barrel-imports`**: Import directly from the file (e.g., `import { Button } from './Button'`), avoid barrel files (`index.js` re-exports) if possible, or ensure tree-shaking works.
- **`bundle-dynamic-imports`**: Use `next/dynamic` or `React.lazy` for heavy components (charts, maps, huge lists).
- **`bundle-defer-third-party`**: Load analytics/logging scripts after hydration or lazily.

### 3. Server-Side Performance (HIGH)
- **`server-cache-react`**: Use `React.cache()` for per-request deduplication of data fetching.
- **`server-serialization`**: Minimize data passed to Client Components (it gets serialized). Use DTOs.
- **`server-parallel-fetching`**: Fetch data in parallel in the parent Server Component when possible.

### 4. Client-Side Data Fetching
- **`client-swr-dedup`**: Use SWR or TanStack Query for automatic request deduplication and caching.

### 5. Re-render Optimization
- **`rerender-memo`**: Use `React.memo`, `useMemo`, and `useCallback` for expensive calculations or to prevent child re-renders.
- **`rerender-dependencies`**: Keep dependency arrays in `useEffect` and `useMemo` precise. Use primitives where possible.
- **`rerender-transitions`**: Use `startTransition` or `useOptimistic` for non-urgent UI updates to keep the interface responsive.

## Server vs. Client Components
- **Server Components (Default)**: Use for data fetching, accessing backend resources, keeping sensitive info (keys) on server, and reducing client bundle size.
- **Client Components (`'use client'`)**: Use for interactivity (onClick, onChange), hooks (useState, useEffect), and browser-only APIs.
