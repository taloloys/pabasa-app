import { usePage } from '@inertiajs/react';

const translations = {
    en: {
        offline: 'Offline — changes are disabled. Showing the list loaded at',
        online: 'Last synced at',
    },
    fil: {
        offline: 'Walang koneksyon — hindi pinagana ang mga pagbabago. Ipinapakita ang listahang na-load noong',
        online: 'Huling na-sync noong',
    },
    ceb: {
        offline: 'Walay koneksyon — gipalong ang mga kausaban. Gipakita ang listahang gi-load niadtong',
        online: 'Kataposang gi-sync niadtong',
    },
};

const localeCodes = {
    en: 'en',
    fil: 'fil-PH',
    ceb: 'ceb-PH',
};

export default function NetworkStatusNotice({ isOnline, lastSyncedAt }) {
    const { locale = 'en' } = usePage().props;
    const labels = translations[locale] ?? translations.en;
    const formattedTime = new Intl.DateTimeFormat(localeCodes[locale] ?? 'en', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(lastSyncedAt);

    return (
        <div
            role={isOnline ? 'status' : 'alert'}
            aria-live="polite"
            className={`rounded-lg border px-4 py-3 text-sm ${
                isOnline
                    ? 'border-stone-200 bg-stone-50 text-stone-700'
                    : 'border-amber-400 bg-amber-50 font-medium text-amber-950'
            }`}
        >
            {isOnline ? labels.online : labels.offline} {formattedTime}
        </div>
    );
}
