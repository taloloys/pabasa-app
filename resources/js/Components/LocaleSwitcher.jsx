import { router, usePage } from '@inertiajs/react';
import { useState } from 'react';

export default function LocaleSwitcher({ className = '' }) {
    const { locale } = usePage().props;
    const [loading, setLoading] = useState(false);

    const locales = [
        { code: 'en', label: 'English', short: 'EN' },
        { code: 'fil', label: 'Filipino', short: 'FIL' },
        { code: 'ceb', label: 'Cebuano', short: 'CEB' },
    ];

    const switchLocale = (newLocale) => {
        if (newLocale === locale || loading) return;

        setLoading(true);
        router.post(
            route('locale.switch'),
            { locale: newLocale },
            {
                preserveScroll: true,
                onFinish: () => setLoading(false),
            }
        );
    };

    return (
        <div className={`flex items-center space-x-1 rounded-xl border border-gold-300/60 bg-white/80 p-1 text-xs font-semibold shadow-2xs backdrop-blur-xs ${className}`}>
            {locales.map((item) => (
                <button
                    key={item.code}
                    type="button"
                    onClick={() => switchLocale(item.code)}
                    disabled={loading}
                    className={`rounded-lg px-2.5 py-1 transition-all duration-150 cursor-pointer ${
                        locale === item.code
                            ? 'bg-gradient-to-r from-liturgical-800 to-liturgical-900 text-gold-200 shadow-xs border border-gold-400/40 font-bold'
                            : 'text-stone-600 hover:text-stone-900 hover:bg-stone-100/70'
                    }`}
                    title={item.label}
                >
                    {item.short}
                </button>
            ))}
        </div>
    );
}
