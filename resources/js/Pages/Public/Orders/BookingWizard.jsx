import ApplicationLogo from '@/Components/ApplicationLogo';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { Head, Link, useForm } from '@inertiajs/react';
import { useMemo, useState } from 'react';

export default function BookingWizard({ church, mass, categories, pricingConfig, user }) {
    const [step, setStep] = useState(1);

    const pricePerIntention = parseFloat(pricingConfig?.price_per_intention || '50.00');
    const currency = pricingConfig?.currency || 'PHP';

    const { data, setData, post, processing, errors } = useForm({
        mass_schedule_id: mass.id,
        submitter_name: user?.name || '',
        submitter_email: user?.email || '',
        privacy_acknowledged: false,
        intentions: [
            {
                category_id: categories[0]?.id || '',
                person_or_family_name: '',
                wording: 'For good health and spiritual guidance.',
            },
        ],
    });

    // Client-side duplicate detection warning
    const duplicateWarnings = useMemo(() => {
        const warnings = [];
        const seen = new Set();
        data.intentions.forEach((int, idx) => {
            const key = `${int.person_or_family_name.trim().toLowerCase()}|${int.wording.trim().toLowerCase()}`;
            if (int.person_or_family_name.trim() && seen.has(key)) {
                warnings.push(`Intention #${idx + 1} ("${int.person_or_family_name}") appears identical to another intention.`);
            }
            if (int.person_or_family_name.trim()) {
                seen.add(key);
            }
        });
        return warnings;
    }, [data.intentions]);

    const addIntention = () => {
        setData('intentions', [
            ...data.intentions,
            {
                category_id: categories[0]?.id || '',
                person_or_family_name: '',
                wording: 'For good health and spiritual guidance.',
            },
        ]);
    };

    const removeIntention = (index) => {
        if (data.intentions.length <= 1) return;
        const updated = [...data.intentions];
        updated.splice(index, 1);
        setData('intentions', updated);
    };

    const updateIntention = (index, field, value) => {
        const updated = [...data.intentions];
        updated[index][field] = value;
        setData('intentions', updated);
    };

    const totalCalculated = (data.intentions.length * pricePerIntention).toFixed(2);

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route('orders.store'));
    };

    return (
        <div className="min-h-screen bg-gradient-to-b from-[#FDFBF7] via-[#FAF6EE] to-[#F5ECE0] text-stone-800 antialiased selection:bg-liturgical-800 selection:text-gold-200">
            <Head title={`Book Prayer Intentions — ${church.name}`} />

            {/* Top Navigation */}
            <header className="border-b border-gold-300/60 bg-white/95 backdrop-blur-md sticky top-0 z-40 shadow-2xs">
                <div className="h-1 bg-gradient-to-r from-liturgical-900 via-gold-500 to-liturgical-900" />
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                    <div className="flex items-center space-x-3">
                        <Link href="/" className="flex items-center space-x-2">
                            <ApplicationLogo className="h-10 w-10 shadow-xs" size="sm" />
                        </Link>
                        <span className="text-stone-300">/</span>
                        <Link href={route('churches.show', { slug: church.slug })} className="text-xs font-semibold text-stone-600 hover:text-liturgical-900 truncate max-w-xs">
                            {church.name}
                        </Link>
                        <span className="text-stone-300">/</span>
                        <span className="text-xs font-bold text-liturgical-950">Book Intentions</span>
                    </div>

                    <div className="text-xs font-medium text-stone-500">
                        Step {step} of 3
                    </div>
                </div>
            </header>

            <main className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
                {/* Stepper Progress Bar */}
                <div className="mb-8">
                    <div className="flex items-center justify-between text-xs font-semibold uppercase tracking-wider text-stone-500">
                        <span className={step >= 1 ? 'text-liturgical-900 font-bold' : ''}>1. Mass Selection</span>
                        <span className={step >= 2 ? 'text-liturgical-900 font-bold' : ''}>2. Prayer Intentions</span>
                        <span className={step >= 3 ? 'text-liturgical-900 font-bold' : ''}>3. Review & Offering</span>
                    </div>
                    <div className="mt-2 h-1.5 w-full bg-stone-200 rounded-full overflow-hidden">
                        <div
                            className="h-full bg-gradient-to-r from-liturgical-900 to-gold-500 transition-all duration-300"
                            style={{ width: `${(step / 3) * 100}%` }}
                        />
                    </div>
                </div>

                <form onSubmit={handleSubmit} className="bg-white rounded-xl border border-gold-300/80 shadow-xs p-6 sm:p-8 space-y-8">
                    {/* Step 1: Mass Schedule Summary */}
                    {step === 1 && (
                        <div className="space-y-6">
                            <div>
                                <h1 className="font-cinzel text-2xl font-bold text-liturgical-950">
                                    Selected Holy Mass
                                </h1>
                                <p className="text-xs text-stone-500 mt-1">
                                    Review the schedule and venue before drafting your intentions.
                                </p>
                            </div>

                            <div className="p-5 rounded-lg bg-stone-50 border border-stone-200 space-y-4 text-xs">
                                <div className="flex items-start justify-between">
                                    <div>
                                        <span className="font-bold text-stone-900 text-base block">{mass.title}</span>
                                        <span className="text-stone-600 block mt-0.5">{church.name}</span>
                                    </div>
                                    <span className="px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-100 text-emerald-800">
                                        Open for Bookings
                                    </span>
                                </div>

                                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-3 border-t border-stone-200">
                                    <div>
                                        <span className="text-stone-500 uppercase font-semibold text-[10px] block">Schedule</span>
                                        <span className="text-stone-800 font-medium block mt-0.5">{mass.starts_at_formatted}</span>
                                    </div>
                                    <div>
                                        <span className="text-stone-500 uppercase font-semibold text-[10px] block">Submission Cutoff</span>
                                        <span className="text-stone-800 font-medium block mt-0.5">{mass.booking_cutoff_formatted}</span>
                                    </div>
                                    <div>
                                        <span className="text-stone-500 uppercase font-semibold text-[10px] block">Location</span>
                                        <span className="text-stone-800 font-medium block mt-0.5">{mass.location_label || 'Main Sanctuary'}</span>
                                    </div>
                                    <div>
                                        <span className="text-stone-500 uppercase font-semibold text-[10px] block">Offering Rate</span>
                                        <span className="text-stone-800 font-medium block mt-0.5">{currency} {pricePerIntention.toFixed(2)} per intention</span>
                                    </div>
                                </div>
                            </div>

                            <div className="flex justify-end">
                                <button
                                    type="button"
                                    onClick={() => setStep(2)}
                                    className="px-5 py-2.5 rounded-sm bg-liturgical-900 text-gold-200 hover:bg-liturgical-800 text-xs font-semibold uppercase tracking-wider transition cursor-pointer"
                                >
                                    Proceed to Intentions &rarr;
                                </button>
                            </div>
                        </div>
                    )}

                    {/* Step 2: Multi-Intention Builder */}
                    {step === 2 && (
                        <div className="space-y-6">
                            <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2">
                                <div>
                                    <h1 className="font-cinzel text-xl font-bold text-liturgical-950">
                                        Draft Prayer Intentions ({data.intentions.length})
                                    </h1>
                                    <p className="text-xs text-stone-500 mt-0.5">
                                        Add names and intentions to be offered during this Holy Mass.
                                    </p>
                                </div>

                                <button
                                    type="button"
                                    onClick={addIntention}
                                    className="inline-flex items-center px-3 py-1.5 rounded-sm border border-gold-400 bg-gold-50 text-gold-900 hover:bg-gold-100 text-xs font-semibold uppercase tracking-wider transition cursor-pointer"
                                >
                                    + Add Another Intention
                                </button>
                            </div>

                            {/* Duplicate warning notification */}
                            {duplicateWarnings.length > 0 && (
                                <div className="p-3.5 rounded-md bg-amber-50 border border-amber-200 text-xs text-amber-900 space-y-1">
                                    <div className="font-semibold flex items-center space-x-1.5">
                                        <svg className="w-4 h-4 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                        </svg>
                                        <span>Duplicate Notice:</span>
                                    </div>
                                    {duplicateWarnings.map((w, idx) => (
                                        <p key={idx} className="pl-5 text-[11px] text-amber-800">{w}</p>
                                    ))}
                                </div>
                            )}

                            {/* Intentions List */}
                            <div className="space-y-4">
                                {data.intentions.map((item, idx) => (
                                    <div key={idx} className="p-4 rounded-lg border border-gold-200/90 bg-stone-50/50 relative space-y-3">
                                        <div className="flex items-center justify-between pb-2 border-b border-stone-200/60">
                                            <span className="font-semibold text-xs text-liturgical-950 uppercase tracking-wider">
                                                Intention #{idx + 1}
                                            </span>
                                            {data.intentions.length > 1 && (
                                                <button
                                                    type="button"
                                                    onClick={() => removeIntention(idx)}
                                                    className="text-stone-400 hover:text-rose-600 text-xs font-medium cursor-pointer"
                                                >
                                                    Remove
                                                </button>
                                            )}
                                        </div>

                                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                            <div>
                                                <InputLabel value="Category *" />
                                                <select
                                                    value={item.category_id}
                                                    onChange={(e) => updateIntention(idx, 'category_id', e.target.value)}
                                                    className="mt-1 block w-full rounded-md border-stone-300 shadow-2xs text-xs focus:border-gold-500 focus:ring-gold-500"
                                                    required
                                                >
                                                    {categories.map((c) => (
                                                        <option key={c.id} value={c.id}>
                                                            {c.label}
                                                        </option>
                                                    ))}
                                                </select>
                                            </div>

                                            <div>
                                                <InputLabel value="Person or Family Name *" />
                                                <TextInput
                                                    type="text"
                                                    className="mt-1 block w-full text-xs"
                                                    value={item.person_or_family_name}
                                                    onChange={(e) => updateIntention(idx, 'person_or_family_name', e.target.value)}
                                                    placeholder="e.g. Juan dela Cruz / Dela Cruz Family"
                                                    required
                                                />
                                            </div>
                                        </div>

                                        <div>
                                            <InputLabel value="Spiritual Intention Wording *" />
                                            <textarea
                                                rows={2}
                                                className="mt-1 block w-full rounded-md border-stone-300 shadow-2xs text-xs focus:border-gold-500 focus:ring-gold-500"
                                                value={item.wording}
                                                onChange={(e) => updateIntention(idx, 'wording', e.target.value)}
                                                placeholder="e.g. In thanksgiving for blessings received / For eternal repose"
                                                required
                                            />
                                        </div>
                                    </div>
                                ))}
                            </div>

                            <div className="flex justify-between items-center pt-4 border-t border-stone-100">
                                <button
                                    type="button"
                                    onClick={() => setStep(1)}
                                    className="text-xs font-semibold uppercase tracking-wider text-stone-600 hover:text-stone-900 cursor-pointer"
                                >
                                    &larr; Back
                                </button>
                                <button
                                    type="button"
                                    onClick={() => setStep(3)}
                                    className="px-5 py-2.5 rounded-sm bg-liturgical-900 text-gold-200 hover:bg-liturgical-800 text-xs font-semibold uppercase tracking-wider transition cursor-pointer"
                                >
                                    Review Summary &rarr;
                                </button>
                            </div>
                        </div>
                    )}

                    {/* Step 3: Review, Contact & Submission */}
                    {step === 3 && (
                        <div className="space-y-6">
                            <div>
                                <h1 className="font-cinzel text-xl font-bold text-liturgical-950">
                                    Review & Submitter Details
                                </h1>
                                <p className="text-xs text-stone-500 mt-0.5">
                                    Confirm your contact information and review the offering amount before submission.
                                </p>
                            </div>

                            {/* Contact fields for Guest or pre-filled for User */}
                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 p-4 rounded-lg bg-stone-50 border border-stone-200">
                                <div>
                                    <InputLabel htmlFor="submitter_name" value="Your Full Name *" />
                                    <TextInput
                                        id="submitter_name"
                                        type="text"
                                        className="mt-1 block w-full text-xs"
                                        value={data.submitter_name}
                                        onChange={(e) => setData('submitter_name', e.target.value)}
                                        required
                                    />
                                    <InputError message={errors.submitter_name} />
                                </div>

                                <div>
                                    <InputLabel htmlFor="submitter_email" value="Your Email Address *" />
                                    <TextInput
                                        id="submitter_email"
                                        type="email"
                                        className="mt-1 block w-full text-xs"
                                        value={data.submitter_email}
                                        onChange={(e) => setData('submitter_email', e.target.value)}
                                        required
                                    />
                                    <InputError message={errors.submitter_email} />
                                </div>
                            </div>

                            {/* Financial Summary */}
                            <div className="p-4 rounded-lg bg-gold-50/70 border border-gold-300/80 space-y-2 text-xs">
                                <span className="font-cinzel font-bold text-stone-900 uppercase tracking-wider block">
                                    Offering Calculation
                                </span>
                                <div className="flex justify-between py-1 border-b border-gold-200">
                                    <span>Intentions Count:</span>
                                    <span className="font-semibold text-stone-800">{data.intentions.length} intention(s)</span>
                                </div>
                                <div className="flex justify-between py-1 border-b border-gold-200">
                                    <span>Approved Rate:</span>
                                    <span>{currency} {pricePerIntention.toFixed(2)} each</span>
                                </div>
                                <div className="flex justify-between py-1 text-sm font-bold text-liturgical-950">
                                    <span>Total Offering Amount:</span>
                                    <span>{currency} {totalCalculated}</span>
                                </div>
                            </div>

                            {/* Privacy Acknowledged */}
                            <div className="flex items-start space-x-2 pt-2">
                                <input
                                    type="checkbox"
                                    id="privacy_acknowledged"
                                    checked={data.privacy_acknowledged}
                                    onChange={(e) => setData('privacy_acknowledged', e.target.checked)}
                                    className="rounded border-stone-300 text-liturgical-900 focus:ring-gold-500 mt-0.5 cursor-pointer"
                                    required
                                />
                                <label htmlFor="privacy_acknowledged" className="text-xs text-stone-600 leading-normal cursor-pointer">
                                    I acknowledge the privacy notice and agree that prayer intention names will be read during the Holy Mass and processed for parish recordkeeping.
                                </label>
                            </div>
                            <InputError message={errors.privacy_acknowledged} />

                            <div className="flex justify-between items-center pt-4 border-t border-stone-100">
                                <button
                                    type="button"
                                    onClick={() => setStep(2)}
                                    className="text-xs font-semibold uppercase tracking-wider text-stone-600 hover:text-stone-900 cursor-pointer"
                                >
                                    &larr; Back
                                </button>
                                <PrimaryButton disabled={processing || !data.privacy_acknowledged} className="cursor-pointer">
                                    {processing ? 'Submitting...' : 'Submit Intentions & Proceed to Payment &rarr;'}
                                </PrimaryButton>
                            </div>
                        </div>
                    )}
                </form>
            </main>
        </div>
    );
}

