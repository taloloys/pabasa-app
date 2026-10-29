import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import InputLabel from '@/Components/InputLabel';
import EmptyState from '@/Components/EmptyState';
import { Head, useForm, usePage } from '@inertiajs/react';

export default function OrderTracking({ orders = [] }) {
    const { auth } = usePage().props;
    const user = auth.user;

    const { data, setData, post, processing } = useForm({
        reference: '',
    });

    const handleSearch = (e) => {
        e.preventDefault();
        // Placeholder for order lookup in Phase 3
    };

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
                                Mass Intention Order Tracking
                            </h2>
                            <span className="inline-flex items-center space-x-1.5 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-800 border border-emerald-200 ring-1 ring-emerald-500/10">
                                <span className="h-1.5 w-1.5 rounded-full bg-emerald-500" />
                                <span>Verified Parishioner</span>
                            </span>
                        </div>
                        <p className="mt-1 text-xs text-stone-600 font-sans">
                            Track the canonical review, GCash payment verification, and ambo lectionary scheduling of your intentions.
                        </p>
                    </div>

                    <div className="text-xs font-serif italic text-gold-800">
                        Oratio &bull; Gratiarum Actio &bull; Requiem
                    </div>
                </div>
            }
        >
            <Head title="Track Intentions - Pabasa" />

            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-8">
                {/* Search Bar Card */}
                <div className="rounded-2xl border border-gold-300/70 bg-white p-7 shadow-xs ring-1 ring-gold-400/20">
                    <form onSubmit={handleSearch} className="max-w-xl">
                        <InputLabel htmlFor="reference" value="Search by Order Public ID or GCash Reference Number" />
                        <div className="mt-2 flex gap-3">
                            <TextInput
                                id="reference"
                                type="text"
                                value={data.reference}
                                onChange={(e) => setData('reference', e.target.value)}
                                className="block w-full font-mono text-xs"
                                placeholder="e.g. ORD-2026-0001 or 901234567890"
                            />
                            <PrimaryButton disabled={processing || !data.reference.trim()}>
                                Search
                            </PrimaryButton>
                        </div>
                        <p className="mt-2 text-[11px] text-stone-500 leading-relaxed font-sans">
                            Phase 3 will activate end-to-end multi-intention creation, GCash payment screenshot uploads, and split-screen verification queues.
                        </p>
                    </form>
                </div>

                {/* Orders List / Empty State */}
                <EmptyState
                    icon="🕊️"
                    title="No Mass Intentions Submitted Yet"
                    description="Once affiliated parishes publish their Mass schedules in Phase 2, you will be able to submit Thanksgiving, Memorial, and Healing prayer requests with GCash receipt verification right here."
                    className="border-gold-300/60 bg-white/90 ring-1 ring-gold-400/20 py-16"
                />
            </div>
        </AuthenticatedLayout>
    );
}
