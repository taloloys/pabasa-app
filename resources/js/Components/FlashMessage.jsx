import { usePage } from '@inertiajs/react';
import { useState, useEffect } from 'react';

export default function FlashMessage() {
    const { flash } = usePage().props;
    const [copied, setCopied] = useState(false);
    const [dismissed, setDismissed] = useState(false);

    useEffect(() => {
        setDismissed(false);
        setCopied(false);

        // Auto-dismiss success and error notifications after 5 seconds
        if (flash?.success || flash?.error) {
            const timer = setTimeout(() => {
                setDismissed(true);
            }, 5000);

            return () => clearTimeout(timer);
        }
    }, [flash?.success, flash?.error, flash?.raw_token]);

    if (dismissed || (!flash?.success && !flash?.error && !flash?.raw_token)) {
        return null;
    }

    const copyToken = (token) => {
        const inviteUrl = `${window.location.origin}/invitations/${token}`;
        navigator.clipboard.writeText(inviteUrl);
        setCopied(true);
        setTimeout(() => setCopied(false), 2500);
    };

    return (
        <div className="mx-auto max-w-7xl px-4 pt-4 sm:px-6 lg:px-8 transition-all duration-300">
            {flash.success && (
                <div className="mb-4 flex items-center justify-between rounded-lg border border-emerald-300 bg-emerald-50 p-4 text-sm text-emerald-800 shadow-xs animate-in fade-in duration-300">
                    <div className="flex items-center space-x-3">
                        <svg className="h-5 w-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span className="font-medium text-xs sm:text-sm">{flash.success}</span>
                    </div>
                    <button
                        type="button"
                        onClick={() => setDismissed(true)}
                        className="text-emerald-500 hover:text-emerald-700 p-1 text-sm font-bold cursor-pointer"
                        title="Dismiss"
                    >
                        ✕
                    </button>
                </div>
            )}

            {flash.error && (
                <div className="mb-4 flex items-center justify-between rounded-lg border border-rose-300 bg-rose-50 p-4 text-sm text-rose-800 shadow-xs animate-in fade-in duration-300">
                    <div className="flex items-center space-x-3">
                        <svg className="h-5 w-5 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span className="font-medium text-xs sm:text-sm">{flash.error}</span>
                    </div>
                    <button
                        type="button"
                        onClick={() => setDismissed(true)}
                        className="text-rose-500 hover:text-rose-700 p-1 text-sm font-bold cursor-pointer"
                        title="Dismiss"
                    >
                        ✕
                    </button>
                </div>
            )}

            {flash.raw_token && (
                <div className="mb-4 rounded-lg border border-indigo-200 bg-indigo-50 p-4 shadow-xs">
                    <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div>
                            <h4 className="text-sm font-semibold text-indigo-900">
                                Staff Invitation Link Generated
                            </h4>
                            <p className="text-xs text-indigo-700 mt-0.5">
                                Send this one-time link directly to the staff member. For security, raw tokens are never displayed again.
                            </p>
                        </div>
                        <button
                            type="button"
                            onClick={() => copyToken(flash.raw_token)}
                            className="inline-flex items-center justify-center rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white shadow-xs hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 cursor-pointer"
                        >
                            {copied ? '✓ Link Copied!' : 'Copy Invitation Link'}
                        </button>
                    </div>
                    <div className="mt-2.5 overflow-x-auto rounded bg-white p-2 font-mono text-xs text-slate-700 border border-indigo-100">
                        {`${typeof window !== 'undefined' ? window.location.origin : ''}/invitations/${flash.raw_token}`}
                    </div>
                </div>
            )}
        </div>
    );
}
