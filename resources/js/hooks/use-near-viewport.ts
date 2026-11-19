import { useEffect, useRef, useState } from 'react';

export function useNearViewport() {
    const ref = useRef<HTMLDivElement>(null);
    const [nearViewport, setNearViewport] = useState(false);

    useEffect(() => {
        const element = ref.current;
        if (!element) return;
        if (!('IntersectionObserver' in window)) {
            setNearViewport(true);
            return;
        }
        const observer = new IntersectionObserver(
            (entries) => {
                if (!entries.some((entry) => entry.isIntersecting)) return;
                setNearViewport(true);
                observer.disconnect();
            },
            { rootMargin: '160px' },
        );
        observer.observe(element);

        return () => observer.disconnect();
    }, []);

    return { ref, nearViewport };
}
