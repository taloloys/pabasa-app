import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import StatusBadge from '@/Components/StatusBadge';
import { Head } from '@inertiajs/react';

export default function LectorDashboard({ church }) {
    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <div className="flex items-center space-x-3">
                            <h2
                                className="text-2xl font-bold tracking-tight text-liturgical-950 font-serif"
                                style={{ fontFamily: 'var(--font-cinzel, Georgia, serif)' }}
                            >
                                Ministry of the Word &bull; Ambo Portal
                            </h2>
                            <StatusBadge role="lector" />
                        </div>
                        <p className="mt-1 text-xs text-stone-600 font-sans">
                            {church.name} &bull; Proclamation of Holy Scriptures & Parish Prayer Intentions
                        </p>
                    </div>

                    <div className="flex items-center space-x-2 text-xs font-serif italic text-gold-800">
                        <span>Ministerium Verbi &bull; Pro Populo Dei</span>
                    </div>
                </div>
            }
        >
            <Head title={`Lector Ambo Portal - ${church.name}`} />

            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-8">
                {/* Liturgical Guidelines Banner */}
                <div className="relative overflow-hidden rounded-3xl border border-gold-300/80 bg-gradient-to-r from-liturgical-900 via-liturgical-950 to-stone-950 p-7 text-gold-100 shadow-lg ring-1 ring-gold-400/30">
                    <div className="relative z-10 flex items-start space-x-5">
                        <div className="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gold-500/20 text-gold-300 border border-gold-400/40 text-2xl shadow-inner">
                            📖
                        </div>
                        <div className="space-y-1.5">
                            <div className="text-[10px] font-serif uppercase tracking-widest text-gold-400">
                                Liturgical Commission
                            </div>
                            <h3
                                className="text-lg font-bold text-white font-serif"
                                style={{ fontFamily: 'var(--font-cinzel, Georgia, serif)' }}
                            >
                                Sacred Ministry of the Liturgical Reader
                            </h3>
                            <p className="text-xs text-stone-300 leading-relaxed max-w-3xl font-sans">
                                As a commissioned lector at {church.name}, you are entrusted with proclaiming approved prayer intentions and sacred readings with dignity, reverence, and vocal clarity during the celebration of the Holy Eucharist.
                            </p>
                        </div>
                    </div>
                </div>

                <div className="grid grid-cols-1 gap-8 lg:grid-cols-2">
                    {/* Assigned Masses Schedule */}
                    <div className="rounded-2xl border border-gold-300/70 bg-white p-7 shadow-xs space-y-4 ring-1 ring-gold-400/20">
                        <div className="flex items-center justify-between border-b border-gold-200/80 pb-3">
                            <h4
                                className="text-base font-bold text-liturgical-950 font-serif"
                                style={{ fontFamily: 'var(--font-cinzel, Georgia, serif)' }}
                            >
                                Assigned Masses
                            </h4>
                            <span className="text-[11px] font-semibold text-gold-800 bg-gold-50 border border-gold-200 px-2.5 py-0.5 rounded-full">
                                Phase 2 Schedule Integration
                            </span>
                        </div>

                        <div className="rounded-2xl border border-dashed border-gold-200 bg-ivory-50/60 p-8 text-center space-y-2">
                            <div className="text-3xl">🕊️</div>
                            <div
                                className="font-bold text-liturgical-950 text-sm font-serif"
                                style={{ fontFamily: 'var(--font-cinzel, Georgia, serif)' }}
                            >
                                No Active Mass Assignments Yet
                            </div>
                            <p className="text-xs text-stone-500 max-w-sm mx-auto leading-relaxed">
                                Once Church Administrators schedule upcoming Masses and assign lectors in Phase 2, your personalized liturgical rosters will appear here.
                            </p>
                        </div>
                    </div>

                    {/* Operational Protocols */}
                    <div className="rounded-2xl border border-gold-300/70 bg-white p-7 shadow-xs space-y-4 ring-1 ring-gold-400/20">
                        <div className="border-b border-gold-200/80 pb-3">
                            <h4
                                className="text-base font-bold text-liturgical-950 font-serif"
                                style={{ fontFamily: 'var(--font-cinzel, Georgia, serif)' }}
                            >
                                Ambo Proclamation Canons
                            </h4>
                        </div>
                        <ul className="space-y-3.5 text-xs text-stone-700">
                            <li className="flex items-start space-x-3">
                                <span className="font-bold text-gold-600 mt-0.5">&bull;</span>
                                <span><strong className="text-liturgical-950">Verbatim Reading:</strong> Parishioner intentions (Thanksgiving, Memorial, Healing) must be pronounced exactly as approved by church administrators.</span>
                            </li>
                            <li className="flex items-start space-x-3">
                                <span className="font-bold text-gold-600 mt-0.5">&bull;</span>
                                <span><strong className="text-liturgical-950">Punctuality:</strong> Arrive at the sacristy at least 20 minutes before Mass to review phonetics and intention finalization.</span>
                            </li>
                            <li className="flex items-start space-x-3">
                                <span className="font-bold text-gold-600 mt-0.5">&bull;</span>
                                <span><strong className="text-liturgical-950">Offline Tablet Caching:</strong> In Phase 4, the ambo lectionary view will cache locally on your tablet for uninterrupted liturgical reading at the ambo without internet connection.</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
