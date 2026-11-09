import { useSyncExternalStore } from 'react';

const unsubscribe = () => {};
const subscribe = () => unsubscribe;

// Keep lazy visuals out of server rendering while preserving their placeholder.
export function useInBrowser(): boolean {
    return useSyncExternalStore(
        subscribe,
        () => true,
        () => false,
    );
}
