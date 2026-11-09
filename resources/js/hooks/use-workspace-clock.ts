import { useEffect, useState } from 'react';

export function useWorkspaceClock(serverNow: number) {
    const [now, setNow] = useState(() => new Date(serverNow * 1000));
    useEffect(() => {
        const offset = serverNow * 1000 - Date.now();
        const tick = () => setNow(new Date(Date.now() + offset));
        tick();
        const interval = window.setInterval(tick, 1000);
        return () => window.clearInterval(interval);
    }, [serverNow]);
    return now;
}
