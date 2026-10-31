import { router } from '@inertiajs/react';
import { useCallback, useEffect, useState } from 'react';

export default function useNetworkStatus() {
    const [isOnline, setIsOnline] = useState(() => (
        typeof navigator === 'undefined' || navigator.onLine
    ));
    const [lastSyncedAt, setLastSyncedAt] = useState(() => new Date());
    const markSynced = useCallback(() => setLastSyncedAt(new Date()), []);

    useEffect(() => {
        const handleOffline = () => setIsOnline(false);
        const handleOnline = () => {
            setIsOnline(true);
            router.reload({
                preserveScroll: true,
                onSuccess: markSynced,
            });
        };

        window.addEventListener('offline', handleOffline);
        window.addEventListener('online', handleOnline);

        return () => {
            window.removeEventListener('offline', handleOffline);
            window.removeEventListener('online', handleOnline);
        };
    }, [markSynced]);

    return { isOnline, lastSyncedAt, markSynced };
}
