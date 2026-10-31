import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import ReadingListWorkbench from './ReadingListWorkbench';
import NetworkStatusNotice from '@/Components/NetworkStatusNotice';
import useNetworkStatus from '@/hooks/useNetworkStatus';

export default function Show({ church, mass, readingList, pendingIntentions, canManageReadingList }) {
    const isOpen = mass.booking_status === 'open' && mass.status !== 'cancelled';
    const massTimezone = mass.timezone || church.timezone || 'Asia/Manila';
    const { isOnline, lastSyncedAt, markSynced } = useNetworkStatus();

    const handleCloseEarly = () => {
        if (!isOnline) return;
        if (!confirm('Are you sure you want to close intention submissions early for this Mass?')) return;
        router.post(route('lector.masses.close-early', { church: church.id, mass: mass.id }), {}, {
            onSuccess: markSynced,
        });
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div>
                        <h1 className="font-cinzel text-xl font-bold text-liturgical-950">
                            {mass.title}
                        </h1>
                        <p className="text-xs text-stone-500 mt-1">
                            Assigned Mass Details &bull; {church.name}
                        </p>
                    </div>

                    <Link
                        href={route('lector.masses.index', { church: church.id })}
                        className="text-xs font-semibold uppercase tracking-wider text-liturgical-900 hover:text-gold-700"
                    >
                        &larr; Back to Assigned Masses
                    </Link>
                </div>
            }
        >
            <Head title={`Mass #${mass.id} — ${church.name}`} />

            <div className="py-8">
                <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
                    <NetworkStatusNotice isOnline={isOnline} lastSyncedAt={lastSyncedAt} />
                    <div className="bg-white rounded-xl border border-gold-300/80 shadow-xs p-6 space-y-6">
                        <div className="flex flex-wrap items-center justify-between gap-4 pb-4 border-b border-stone-100">
                            <div>
                                <span className="text-xs uppercase tracking-wider text-stone-400 font-bold block">
                                    Status
                                </span>
                                <div className="mt-1 flex items-center space-x-2">
                                    <span
                                        className={`inline-flex items-center px-2.5 py-0.5 rounded text-xs font-bold ${
                                            isOpen
                                                ? 'bg-emerald-100 text-emerald-800'
                                                : 'bg-stone-100 text-stone-600'
                                        }`}
                                    >
                                        {isOpen ? 'Open for Intentions' : 'Submissions Closed'}
                                    </span>
                                    <span className="text-xs text-stone-500 font-mono">
                                        ID: #{mass.id}
                                    </span>
                                </div>
                            </div>

                            {isOpen && (
                                <button
                                    type="button"
                                    disabled={!isOnline}
                                    onClick={handleCloseEarly}
                                    className="px-4 py-2 rounded-md border border-amber-300 bg-amber-50 text-amber-800 text-xs font-semibold uppercase tracking-wider hover:bg-amber-100 cursor-pointer disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    Close Submissions Early
                                </button>
                            )}
                        </div>

                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-6 text-xs">
                            <div>
                                <span className="font-semibold text-stone-500 block uppercase tracking-wider">
                                    Scheduled Time
                                </span>
                                <span className="font-bold text-stone-900 text-sm mt-1 block">
                                    {new Date(mass.starts_at).toLocaleString([], {
                                        weekday: 'long',
                                        month: 'short',
                                        day: 'numeric',
                                        year: 'numeric',
                                        hour: '2-digit',
                                        minute: '2-digit',
                                        timeZone: massTimezone,
                                    })}
                                </span>
                            </div>

                            <div>
                                <span className="font-semibold text-stone-500 block uppercase tracking-wider">
                                    Submission Cutoff Deadline
                                </span>
                                <span className="font-bold text-stone-900 text-sm mt-1 block">
                                    {new Date(mass.booking_cutoff_at).toLocaleString([], {
                                        month: 'short',
                                        day: 'numeric',
                                        hour: '2-digit',
                                        minute: '2-digit',
                                        timeZone: massTimezone,
                                    })}
                                </span>
                            </div>

                            <div>
                                <span className="font-semibold text-stone-500 block uppercase tracking-wider">
                                    Reading List Finalization Deadline
                                </span>
                                <span className="font-bold text-stone-900 text-sm mt-1 block">
                                    {mass.finalization_deadline
                                        ? new Date(mass.finalization_deadline).toLocaleString([], {
                                            month: 'short',
                                            day: 'numeric',
                                            hour: '2-digit',
                                            minute: '2-digit',
                                            timeZone: massTimezone,
                                        })
                                        : 'Not configured'}
                                </span>
                            </div>

                            <div>
                                <span className="font-semibold text-stone-500 block uppercase tracking-wider">
                                    Location / Venue
                                </span>
                                <span className="font-medium text-stone-800 text-sm mt-1 block">
                                    {mass.location_label || 'Main Sanctuary'}
                                </span>
                            </div>

                            <div>
                                <span className="font-semibold text-stone-500 block uppercase tracking-wider">
                                    Language
                                </span>
                                <span className="font-medium text-stone-800 text-sm mt-1 block">
                                    {mass.language}
                                </span>
                            </div>
                        </div>

                    </div>
                    <ReadingListWorkbench
                        church={church}
                        mass={mass}
                        readingList={readingList}
                        pendingIntentions={pendingIntentions}
                        canManageReadingList={canManageReadingList}
                        isOnline={isOnline}
                        markSynced={markSynced}
                    />
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
