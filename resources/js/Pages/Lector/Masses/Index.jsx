import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import EmptyState from '@/Components/EmptyState';
import { Head, Link, router } from '@inertiajs/react';

export default function Index({ church, assignedMasses }) {
    const handleCloseEarly = (massId) => {
        if (!confirm('Are you sure you want to close intention submissions early for this Mass?')) return;
        router.post(route('lector.masses.close-early', { church: church.id, mass: massId }));
    };

    return (
        <AuthenticatedLayout
            header={
                <div>
                    <h1 className="font-cinzel text-xl font-bold text-liturgical-950">
                        My Assigned Masses
                    </h1>
                    <p className="text-xs text-stone-500 mt-1">
                        Upcoming Holy Masses where you are actively assigned as lector at {church.name}.
                    </p>
                </div>
            }
        >
            <Head title={`My Assigned Masses — ${church.name}`} />

            <div className="py-8">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="bg-white rounded-xl border border-gold-300/80 shadow-xs overflow-hidden">
                        <div className="p-4 border-b border-stone-100 flex items-center justify-between">
                            <h2 className="font-cinzel text-base font-bold text-liturgical-950">
                                Assigned Mass Roster ({assignedMasses.total ?? assignedMasses.data?.length ?? 0})
                            </h2>
                        </div>

                        {assignedMasses.data.length === 0 ? (
                            <EmptyState
                                title="No Assigned Masses"
                                description="You are not currently assigned to any upcoming Masses. Your parish administrator will assign you to upcoming schedules."
                            />
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-left text-xs text-stone-600">
                                    <thead className="bg-stone-50 text-[11px] uppercase tracking-wider text-stone-500 border-b border-stone-200">
                                        <tr>
                                            <th className="py-3 px-4">Mass Title</th>
                                            <th className="py-3 px-4">Date & Time</th>
                                            <th className="py-3 px-4">Chapel Location</th>
                                            <th className="py-3 px-4">Booking Status</th>
                                            <th className="py-3 px-4 text-right">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-stone-100">
                                        {assignedMasses.data.map((mass) => {
                                            const isOpen = mass.booking_status === 'open' && mass.status !== 'cancelled';

                                            return (
                                                <tr key={mass.id} className="hover:bg-gold-50/30 transition">
                                                    <td className="py-3 px-4">
                                                        <span className="font-bold text-stone-900 block">{mass.title}</span>
                                                        <span className="text-[10px] text-gold-700 bg-gold-50 px-1.5 py-0.2 rounded border border-gold-200 inline-block mt-0.5">
                                                            {mass.language}
                                                        </span>
                                                    </td>

                                                    <td className="py-3 px-4 whitespace-nowrap">
                                                        <span className="font-semibold text-stone-800">
                                                            {new Date(mass.starts_at).toLocaleString([], {
                                                                month: 'short',
                                                                day: 'numeric',
                                                                hour: '2-digit',
                                                                minute: '2-digit',
                                                            })}
                                                        </span>
                                                    </td>

                                                    <td className="py-3 px-4">
                                                        <span>{mass.location_label || 'Sanctuary'}</span>
                                                    </td>

                                                    <td className="py-3 px-4">
                                                        {isOpen ? (
                                                            <span className="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                                                Open
                                                            </span>
                                                        ) : (
                                                            <span className="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-stone-100 text-stone-600">
                                                                Closed
                                                            </span>
                                                        )}
                                                    </td>

                                                    <td className="py-3 px-4 text-right space-x-2 whitespace-nowrap">
                                                        {isOpen && (
                                                            <button
                                                                onClick={() => handleCloseEarly(mass.id)}
                                                                className="px-2.5 py-1 rounded border border-amber-300 bg-amber-50 text-amber-800 text-[10px] font-semibold uppercase hover:bg-amber-100 cursor-pointer"
                                                            >
                                                                Close Submissions
                                                            </button>
                                                        )}

                                                        <Link
                                                            href={route('lector.masses.show', { church: church.id, mass: mass.id })}
                                                            className="px-2.5 py-1 rounded bg-liturgical-900 text-gold-200 hover:bg-liturgical-800 text-[10px] font-semibold uppercase tracking-wider transition"
                                                        >
                                                            View Details &rarr;
                                                        </Link>
                                                    </td>
                                                </tr>
                                            );
                                        })}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

