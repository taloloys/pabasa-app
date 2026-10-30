import ApplicationLogo from '@/Components/ApplicationLogo';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { Head, Link, useForm } from '@inertiajs/react';
import { useMemo, useState } from 'react';

export default function BookingWizard({ church, mass, categories, pricingConfig, user }) {
    const [step, setStep] = useState(1);

    const hasApprovedPricing = Boolean(pricingConfig && pricingConfig.price_per_intention);
    const basePricePerIntention = hasApprovedPricing ? parseFloat(pricingConfig.price_per_intention) : 0;
    const currency = pricingConfig?.currency || 'PHP';

    // Service fee derived from pricing config (display only — authoritative calculation is server-side)
    const feeCfg = pricingConfig?.service_fee ?? null;
    const feeActive = feeCfg?.is_active ?? false;

    const getCategoryPrice = (catId) => {
        const cat = categories.find((c) => String(c.id) === String(catId));
        if (cat && cat.price !== null && cat.price !== undefined) {
            return parseFloat(cat.price);
        }
        return basePricePerIntention;
    };

    const initialCategoryId = categories[0]?.id || '';
    const initialCategory = categories.find((c) => String(c.id) === String(initialCategoryId));
    const initialTemplate = initialCategory?.template_type || 'standard';

    const { data, setData, post, processing, errors } = useForm({
        mass_schedule_id: mass.id,
        submitter_name: user?.name || '',
        submitter_email: user?.email || '',
        privacy_acknowledged: false,
        intentions: [
            {
                category_id: initialCategoryId,
                person_or_family_name: user?.name || '',
                wording: 'For good health and spiritual guidance.',
                offerer_name: user?.name || '',
                contact_no: '',
                thanksgiving_occasions: {},
                souls_list: [''],
                special_intention_text: '',
            },
        ],
    });

    const getSlipPrice = (catId) => getCategoryPrice(catId);

    const totalCalculated = useMemo(() => {
        return data.intentions
            .reduce((sum, item) => sum + getSlipPrice(item.category_id), 0)
            .toFixed(2);
    }, [data.intentions, categories, basePricePerIntention]);

    // Client-side indicative fee calculation for display (server is authoritative)
    const estimatedFee = useMemo(() => {
        if (!feeActive || !feeCfg) return '0.00';
        const subtotal = parseFloat(totalCalculated);
        const rate = parseFloat(feeCfg.amount ?? '0');
        switch (feeCfg.type) {
            case 'fixed_per_order': return rate.toFixed(2);
            case 'fixed_per_slip':  return (rate * data.intentions.length).toFixed(2);
            case 'percentage':      return ((subtotal * rate) / 100).toFixed(2);
            default: return '0.00';
        }
    }, [totalCalculated, feeCfg, feeActive, data.intentions.length]);

    // Client-side duplicate detection warning
    const duplicateWarnings = useMemo(() => {
        const warnings = [];
        const seen = new Set();
        data.intentions.forEach((int, idx) => {
            const key = `${int.person_or_family_name.trim().toLowerCase()}|${int.wording.trim().toLowerCase()}`;
            if (int.person_or_family_name.trim() && seen.has(key)) {
                warnings.push(`Slip #${idx + 1} ("${int.person_or_family_name}") appears identical to another intention in this submission.`);
            }
            if (int.person_or_family_name.trim()) {
                seen.add(key);
            }
        });
        return warnings;
    }, [data.intentions]);

    const addIntention = () => {
        const defaultCat = categories[0] || {};
        setData('intentions', [
            ...data.intentions,
            {
                category_id: defaultCat.id || '',
                person_or_family_name: data.submitter_name || '',
                wording: 'For good health and spiritual guidance.',
                offerer_name: data.submitter_name || '',
                contact_no: '',
                thanksgiving_occasions: {},
                souls_list: [''],
                special_intention_text: '',
            },
        ]);
    };

    const removeIntention = (index) => {
        if (data.intentions.length <= 1) return;
        const updated = [...data.intentions];
        updated.splice(index, 1);
        setData('intentions', updated);
    };

    // Helper to format intention wording and donor name based on template
    const compileSlipWording = (slip, cat) => {
        const template = cat?.template_type || 'standard';
        const offerer = slip.offerer_name || slip.person_or_family_name || 'Offerer';

        if (template === 'thanksgiving') {
            const occasions = Object.entries(slip.thanksgiving_occasions || {})
                .filter(([_, val]) => val !== false && val !== undefined)
                .map(([name, detail]) => (detail && typeof detail === 'string' && detail.trim() ? `• ${name}: ${detail.trim()}` : `• ${name}`));

            const occasionsStr = occasions.length > 0 ? occasions.join('\n') : '• General Thanksgiving for blessings received';
            return {
                person_or_family_name: offerer,
                wording: `Thanksgiving Mass for:\n${occasionsStr}\nOFFERER: ${offerer}`,
            };
        }

        if (template === 'souls') {
            const souls = (slip.souls_list || [])
                .map((s) => s.trim())
                .filter((s) => s.length > 0);

            const soulsStr = souls.length > 0
                ? souls.map((s, idx) => `${idx + 1}. ${s}`).join('\n')
                : '1. All Souls in Purgatory';

            const contactStr = slip.contact_no ? ` | Contact: ${slip.contact_no}` : '';
            return {
                person_or_family_name: offerer,
                wording: `MASS FOR THE SOULS OF:\n${soulsStr}\nOFFERER: ${offerer}${contactStr}`,
            };
        }

        if (template === 'special') {
            const text = slip.special_intention_text || slip.wording || 'Special spiritual intentions.';
            const contactStr = slip.contact_no ? ` | Contact: ${slip.contact_no}` : '';
            return {
                person_or_family_name: offerer,
                wording: `Special Mass Intention:\n${text}\nOFFERER: ${offerer}${contactStr}`,
            };
        }

        return {
            person_or_family_name: slip.person_or_family_name || offerer,
            wording: slip.wording,
        };
    };

    const updateSlipField = (index, field, value) => {
        const updated = [...data.intentions];
        updated[index][field] = value;

        // When category changes, reset template-specific fields
        if (field === 'category_id') {
            const newCat = categories.find((c) => String(c.id) === String(value));
            const newTemplate = newCat?.template_type || 'standard';
            if (newTemplate === 'thanksgiving' && !updated[index].thanksgiving_occasions) {
                updated[index].thanksgiving_occasions = {};
            } else if (newTemplate === 'souls' && (!updated[index].souls_list || updated[index].souls_list.length === 0)) {
                updated[index].souls_list = [''];
            }
        }

        const currentCat = categories.find((c) => String(c.id) === String(updated[index].category_id));
        const compiled = compileSlipWording(updated[index], currentCat);
        updated[index].person_or_family_name = compiled.person_or_family_name;
        updated[index].wording = compiled.wording;

        setData('intentions', updated);
    };

    const toggleThanksgivingOccasion = (index, occasionName, checked) => {
        const updated = [...data.intentions];
        const occ = { ...(updated[index].thanksgiving_occasions || {}) };
        if (checked) {
            occ[occasionName] = occ[occasionName] || '';
        } else {
            delete occ[occasionName];
        }
        updated[index].thanksgiving_occasions = occ;

        const currentCat = categories.find((c) => String(c.id) === String(updated[index].category_id));
        const compiled = compileSlipWording(updated[index], currentCat);
        updated[index].person_or_family_name = compiled.person_or_family_name;
        updated[index].wording = compiled.wording;

        setData('intentions', updated);
    };

    const updateOccasionDetail = (index, occasionName, detailValue) => {
        const updated = [...data.intentions];
        const occ = { ...(updated[index].thanksgiving_occasions || {}) };
        occ[occasionName] = detailValue;
        updated[index].thanksgiving_occasions = occ;

        const currentCat = categories.find((c) => String(c.id) === String(updated[index].category_id));
        const compiled = compileSlipWording(updated[index], currentCat);
        updated[index].person_or_family_name = compiled.person_or_family_name;
        updated[index].wording = compiled.wording;

        setData('intentions', updated);
    };

    const updateSoulItem = (slipIndex, soulIndex, soulName) => {
        const updated = [...data.intentions];
        const list = [...(updated[slipIndex].souls_list || [])];
        list[soulIndex] = soulName;
        updated[slipIndex].souls_list = list;

        const currentCat = categories.find((c) => String(c.id) === String(updated[slipIndex].category_id));
        const compiled = compileSlipWording(updated[slipIndex], currentCat);
        updated[slipIndex].person_or_family_name = compiled.person_or_family_name;
        updated[slipIndex].wording = compiled.wording;

        setData('intentions', updated);
    };

    const addSoulLine = (slipIndex, maxAllowed) => {
        const updated = [...data.intentions];
        const list = [...(updated[slipIndex].souls_list || [])];
        if (list.length < maxAllowed) {
            list.push('');
            updated[slipIndex].souls_list = list;
            setData('intentions', updated);
        }
    };

    const removeSoulLine = (slipIndex, soulIndex) => {
        const updated = [...data.intentions];
        const list = [...(updated[slipIndex].souls_list || [])];
        if (list.length > 1) {
            list.splice(soulIndex, 1);
            updated[slipIndex].souls_list = list;

            const currentCat = categories.find((c) => String(c.id) === String(updated[slipIndex].category_id));
            const compiled = compileSlipWording(updated[slipIndex], currentCat);
            updated[slipIndex].person_or_family_name = compiled.person_or_family_name;
            updated[slipIndex].wording = compiled.wording;

            setData('intentions', updated);
        }
    };

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
                        <Link
                            href={user ? route('dashboard') : '/'}
                            className="flex items-center space-x-2"
                            title={user ? 'Return to Sanctuary Dashboard' : 'Pabasa Home'}
                        >
                            <ApplicationLogo className="h-10 w-10 shadow-xs" size="sm" />
                        </Link>
                        <span className="text-stone-300">/</span>
                        <Link href={route('churches.show', { slug: church.slug })} className="text-xs font-semibold text-stone-600 hover:text-liturgical-900 truncate max-w-xs">
                            {church.name}
                        </Link>
                        <span className="text-stone-300">/</span>
                        <span className="text-xs font-bold text-liturgical-950">Intention Slips</span>
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
                        <span className={step >= 2 ? 'text-liturgical-900 font-bold' : ''}>2. Fill Out Slips</span>
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
                                    Review the schedule and venue before filling out your parish slips.
                                </p>
                            </div>

                            <div className="p-5 rounded-lg bg-stone-50 border border-stone-200 space-y-4 text-xs">
                                <div className="flex items-start justify-between">
                                    <div>
                                        <span className="font-bold text-stone-900 text-base block">{mass.title}</span>
                                        <span className="text-stone-600 block mt-0.5">{church.name}</span>
                                    </div>
                                    <span className="px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-100 text-emerald-800">
                                        Open for Submissions
                                    </span>
                                </div>

                                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-3 border-t border-stone-200">
                                    <div>
                                        <span className="text-stone-500 uppercase font-semibold text-[10px] block">Schedule</span>
                                        <span className="text-stone-800 font-medium block mt-0.5">{mass.starts_at_formatted}</span>
                                    </div>
                                    <div>
                                        <span className="text-stone-500 uppercase font-semibold text-[10px] block">Cutoff Deadline</span>
                                        <span className="text-stone-800 font-medium block mt-0.5">{mass.booking_cutoff_formatted}</span>
                                    </div>
                                    <div>
                                        <span className="text-stone-500 uppercase font-semibold text-[10px] block">Sanctuary / Location</span>
                                        <span className="text-stone-800 font-medium block mt-0.5">{mass.location_label || 'Main Sanctuary'}</span>
                                    </div>
                                    <div>
                                        <span className="text-stone-500 uppercase font-semibold text-[10px] block">Standard Offering</span>
                                        <span className="text-stone-800 font-medium block mt-0.5">{currency} {basePricePerIntention.toFixed(2)} per slip</span>
                                    </div>
                                </div>
                            </div>

                            <div className="flex justify-end">
                                <button
                                    type="button"
                                    onClick={() => setStep(2)}
                                    className="px-5 py-2.5 rounded-sm bg-liturgical-900 text-gold-200 hover:bg-liturgical-800 text-xs font-semibold uppercase tracking-wider transition cursor-pointer"
                                >
                                    Proceed to Fill Out Slips &rarr;
                                </button>
                            </div>
                        </div>
                    )}

                    {/* Step 2: Multi-Slip Builder (Customized to Match Physical Slips) */}
                    {step === 2 && (
                        <div className="space-y-6">
                            <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2">
                                <div>
                                    <h1 className="font-cinzel text-xl font-bold text-liturgical-950">
                                        Parish Intention Slips ({data.intentions.length})
                                    </h1>
                                    <p className="text-xs text-stone-500 mt-0.5">
                                        Select the slip service and fill out the details according to the parish forms.
                                    </p>
                                </div>

                                <button
                                    type="button"
                                    onClick={addIntention}
                                    className="inline-flex items-center px-3 py-1.5 rounded-sm border border-gold-400 bg-gold-50 text-gold-900 hover:bg-gold-100 text-xs font-semibold uppercase tracking-wider transition cursor-pointer"
                                >
                                    + Add Another Slip
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

                            {/* Slips List */}
                            <div className="space-y-6">
                                {data.intentions.map((item, idx) => {
                                    const currentCat = categories.find((c) => String(c.id) === String(item.category_id)) || categories[0] || {};
                                    const template = currentCat.template_type || 'standard';
                                    const slipPrice = getSlipPrice(item.category_id);
                                    const formConfig = currentCat.form_config || {};
                                    const maxSouls = formConfig.max_souls || 10;
                                    const occasionsList = formConfig.occasions || [
                                        'Birthday Anniversary',
                                        'Wedding Anniversary',
                                        'Anniversary',
                                        'Passing the Exam',
                                        'New Job/Promotion',
                                        'Recovery/ Good health',
                                        'Graces & Blessing received',
                                        'Good Harvest',
                                    ];

                                    return (
                                        <div
                                            key={idx}
                                            className="rounded-xl border-2 border-gold-300/80 bg-[#FCFAF6] shadow-sm relative overflow-hidden"
                                        >
                                            {/* Slip Header Bar */}
                                            <div className="bg-stone-100/80 px-4 py-2.5 border-b border-gold-200 flex flex-wrap items-center justify-between gap-2">
                                                <div className="flex items-center space-x-2">
                                                    <span className="font-cinzel font-bold text-xs text-liturgical-950 uppercase tracking-wider">
                                                        Slip #{idx + 1}
                                                    </span>
                                                    <span className="text-stone-300">|</span>
                                                    <select
                                                        value={item.category_id}
                                                        onChange={(e) => updateSlipField(idx, 'category_id', e.target.value)}
                                                        className="rounded border-stone-300 shadow-2xs text-xs font-semibold text-stone-800 py-1 pl-2 pr-7 focus:border-gold-500 focus:ring-gold-500"
                                                    >
                                                        {categories.map((c) => (
                                                            <option key={c.id} value={c.id}>
                                                                {c.label} (₱ {parseFloat(c.price || basePricePerIntention).toFixed(2)})
                                                            </option>
                                                        ))}
                                                    </select>
                                                </div>

                                                <div className="flex items-center space-x-3">
                                                    <span className="font-mono text-xs font-bold text-liturgical-900 px-2 py-0.5 rounded bg-gold-100/80 border border-gold-300">
                                                        {currency} {slipPrice.toFixed(2)}
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
                                            </div>

                                            {/* Slip Body: Custom Template Rendering */}
                                            <div className="p-5 sm:p-6 space-y-5">
                                                {/* ================= FORM 1: THANKSGIVING MASS ================= */}
                                                {template === 'thanksgiving' && (
                                                    <div className="space-y-4">
                                                        <div className="border-b-2 border-stone-300 pb-2 flex justify-between items-baseline">
                                                            <h3 className="font-serif text-sm font-bold uppercase tracking-wider text-stone-900">
                                                                THANKSGIVING MASS for
                                                            </h3>
                                                            <span className="font-serif italic text-xs font-bold text-stone-600">
                                                                ₱{slipPrice.toFixed(2)}
                                                            </span>
                                                        </div>

                                                        {/* Checkbox Options */}
                                                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                                                            {occasionsList.map((occName, occIdx) => {
                                                                const isChecked = item.thanksgiving_occasions && item.thanksgiving_occasions[occName] !== undefined;
                                                                const detail = isChecked ? item.thanksgiving_occasions[occName] : '';

                                                                return (
                                                                    <div key={occIdx} className="space-y-1">
                                                                        <label className="flex items-start space-x-2 cursor-pointer text-xs font-medium text-stone-800">
                                                                            <input
                                                                                type="checkbox"
                                                                                checked={isChecked}
                                                                                onChange={(e) => toggleThanksgivingOccasion(idx, occName, e.target.checked)}
                                                                                className="mt-0.5 rounded border-stone-300 text-liturgical-900 focus:ring-gold-500 cursor-pointer"
                                                                            />
                                                                            <span>[ &nbsp;] {occName}</span>
                                                                        </label>

                                                                        {isChecked && (
                                                                            <input
                                                                                type="text"
                                                                                value={detail}
                                                                                onChange={(e) => updateOccasionDetail(idx, occName, e.target.value)}
                                                                                placeholder="Celebrant name, exam, or detail..."
                                                                                className="ml-5 block w-[calc(100%-1.25rem)] text-xs rounded border-stone-300 shadow-2xs py-1 px-2 focus:border-gold-500 focus:ring-gold-500 bg-white"
                                                                            />
                                                                        )}
                                                                    </div>
                                                                );
                                                            })}
                                                        </div>

                                                        {/* Offerer and Mass Schedule Lines */}
                                                        <div className="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-4 border-t border-stone-200">
                                                            <div className="sm:col-span-2">
                                                                <InputLabel value="OFFERER: *" />
                                                                <TextInput
                                                                    type="text"
                                                                    value={item.offerer_name || ''}
                                                                    onChange={(e) => updateSlipField(idx, 'offerer_name', e.target.value)}
                                                                    placeholder="e.g. Santos Family / Maria Santos"
                                                                    className="mt-1 block w-full text-xs"
                                                                    required
                                                                />
                                                            </div>
                                                            <div>
                                                                <InputLabel value="Date & Time" />
                                                                <div className="mt-1 py-1.5 px-2 bg-stone-100 rounded text-xs text-stone-600 font-medium">
                                                                    {mass.starts_at_formatted}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                )}

                                                {/* ================= FORM 2: MASS FOR THE SOULS ================= */}
                                                {template === 'souls' && (
                                                    <div className="space-y-4">
                                                        <div className="border-b-2 border-stone-300 pb-2 flex justify-between items-baseline">
                                                            <div>
                                                                <div className="text-[10px] uppercase font-bold text-stone-500 tracking-wider">
                                                                    Archdiocese of Palo • Mother of the Redeemer Parish
                                                                </div>
                                                                <h3 className="font-serif text-sm font-bold uppercase tracking-wider text-stone-900 mt-0.5">
                                                                    MASS FOR THE SOULS OF
                                                                </h3>
                                                            </div>
                                                            <span className="font-serif italic text-xs font-bold text-stone-600">
                                                                ₱{slipPrice.toFixed(2)} (Up to {maxSouls} souls)
                                                            </span>
                                                        </div>

                                                        {/* Numbered Soul Inputs */}
                                                        <div className="space-y-2 pt-1">
                                                            {(item.souls_list || ['']).map((soulName, sIdx) => (
                                                                <div key={sIdx} className="flex items-center space-x-2">
                                                                    <span className="font-mono text-xs font-bold text-stone-500 w-6 text-right">
                                                                        {sIdx + 1}.
                                                                    </span>
                                                                    <TextInput
                                                                        type="text"
                                                                        value={soulName}
                                                                        onChange={(e) => updateSoulItem(idx, sIdx, e.target.value)}
                                                                        placeholder={`Name of departed soul #${sIdx + 1}...`}
                                                                        className="flex-1 text-xs"
                                                                    />
                                                                    {(item.souls_list || []).length > 1 && (
                                                                        <button
                                                                            type="button"
                                                                            onClick={() => removeSoulLine(idx, sIdx)}
                                                                            className="text-stone-400 hover:text-rose-500 text-xs px-1 cursor-pointer"
                                                                            title="Remove line"
                                                                        >
                                                                            &times;
                                                                        </button>
                                                                    )}
                                                                </div>
                                                            ))}

                                                            {(item.souls_list || []).length < maxSouls && (
                                                                <button
                                                                    type="button"
                                                                    onClick={() => addSoulLine(idx, maxSouls)}
                                                                    className="mt-2 text-xs font-semibold text-liturgical-900 hover:text-liturgical-700 underline cursor-pointer pl-8"
                                                                >
                                                                    + Add Soul Line (up to {maxSouls})
                                                                </button>
                                                            )}
                                                        </div>

                                                        {/* Bottom Slip Details */}
                                                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-4 border-t border-stone-200">
                                                            <div>
                                                                <InputLabel value="OFFERER: *" />
                                                                <TextInput
                                                                    type="text"
                                                                    value={item.offerer_name || ''}
                                                                    onChange={(e) => updateSlipField(idx, 'offerer_name', e.target.value)}
                                                                    placeholder="e.g. Dela Cruz Family"
                                                                    className="mt-1 block w-full text-xs"
                                                                    required
                                                                />
                                                            </div>
                                                            <div>
                                                                <InputLabel value="Contact No:" />
                                                                <TextInput
                                                                    type="text"
                                                                    value={item.contact_no || ''}
                                                                    onChange={(e) => updateSlipField(idx, 'contact_no', e.target.value)}
                                                                    placeholder="e.g. 0999-416-5258"
                                                                    className="mt-1 block w-full text-xs"
                                                                />
                                                            </div>
                                                        </div>
                                                    </div>
                                                )}

                                                {/* ================= FORM 3: SPECIAL MASS INTENTION ================= */}
                                                {template === 'special' && (
                                                    <div className="space-y-4">
                                                        <div className="border-b-2 border-stone-300 pb-2 flex justify-between items-baseline">
                                                            <div>
                                                                <div className="text-[10px] uppercase font-bold text-stone-500 tracking-wider">
                                                                    Archdiocese of Palo • Mother of the Redeemer Parish
                                                                </div>
                                                                <h3 className="font-serif text-sm font-bold uppercase tracking-wider text-liturgical-900 mt-0.5">
                                                                    Special Mass Intention
                                                                </h3>
                                                            </div>
                                                            <span className="font-serif italic text-xs font-bold text-stone-600">
                                                                ₱{slipPrice.toFixed(2)}
                                                            </span>
                                                        </div>

                                                        <div>
                                                            <InputLabel value="Spiritual Petitions / Intentions *" />
                                                            <textarea
                                                                rows={4}
                                                                value={item.special_intention_text || ''}
                                                                onChange={(e) => updateSlipField(idx, 'special_intention_text', e.target.value)}
                                                                placeholder="Write your prayer intentions and spiritual petitions..."
                                                                className="mt-1 block w-full rounded-md border-stone-300 shadow-2xs text-xs focus:border-gold-500 focus:ring-gold-500 leading-relaxed font-serif"
                                                                required
                                                            />
                                                        </div>

                                                        {/* Offerer & Contact */}
                                                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                                                            <div>
                                                                <InputLabel value="Offerer: *" />
                                                                <TextInput
                                                                    type="text"
                                                                    value={item.offerer_name || ''}
                                                                    onChange={(e) => updateSlipField(idx, 'offerer_name', e.target.value)}
                                                                    placeholder="Your Name / Family Name"
                                                                    className="mt-1 block w-full text-xs"
                                                                    required
                                                                />
                                                            </div>
                                                            <div>
                                                                <InputLabel value="Contact No.:" />
                                                                <TextInput
                                                                    type="text"
                                                                    value={item.contact_no || ''}
                                                                    onChange={(e) => updateSlipField(idx, 'contact_no', e.target.value)}
                                                                    placeholder="e.g. 0917-682-0001"
                                                                    className="mt-1 block w-full text-xs"
                                                                />
                                                            </div>
                                                        </div>

                                                        {/* Canonical Arancel Notice Note */}
                                                        <div className="p-3 bg-stone-100/90 rounded border border-stone-200 text-[11px] text-stone-600 space-y-1">
                                                            <div className="font-semibold text-stone-700 uppercase tracking-wider text-[10px]">
                                                                Parish Note:
                                                            </div>
                                                            <p>1. We do not pay for the mass. Your donation (Arancel) is for the Parish Church operational expenses and evangelization programs.</p>
                                                            <p>2. The inclusion in the intention does not substitute for the donor's presence in the Holy Mass.</p>
                                                            <p>3. Write your intentions legibly.</p>
                                                        </div>
                                                    </div>
                                                )}

                                                {/* ================= STANDARD TEMPLATE ================= */}
                                                {template === 'standard' && (
                                                    <div className="space-y-4">
                                                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                                            <div>
                                                                <InputLabel value="Person or Family Name *" />
                                                                <TextInput
                                                                    type="text"
                                                                    className="mt-1 block w-full text-xs"
                                                                    value={item.person_or_family_name}
                                                                    onChange={(e) => updateSlipField(idx, 'person_or_family_name', e.target.value)}
                                                                    placeholder="e.g. Juan dela Cruz / Dela Cruz Family"
                                                                    required
                                                                />
                                                            </div>
                                                            <div>
                                                                <InputLabel value="Offering Rate" />
                                                                <div className="mt-1 py-2 px-3 bg-stone-100 rounded text-xs font-mono font-bold text-stone-800">
                                                                    {currency} {slipPrice.toFixed(2)}
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div>
                                                            <InputLabel value="Spiritual Intention Wording *" />
                                                            <textarea
                                                                rows={2}
                                                                className="mt-1 block w-full rounded-md border-stone-300 shadow-2xs text-xs focus:border-gold-500 focus:ring-gold-500"
                                                                value={item.wording}
                                                                onChange={(e) => updateSlipField(idx, 'wording', e.target.value)}
                                                                placeholder="e.g. In thanksgiving for blessings received / For eternal repose"
                                                                required
                                                            />
                                                        </div>
                                                    </div>
                                                )}
                                            </div>
                                        </div>
                                    );
                                })}
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
                                    Review Slips & Submitter Details
                                </h1>
                                <p className="text-xs text-stone-500 mt-0.5">
                                    Confirm your contact information and review the total offering amount before proceeding to payment.
                                </p>
                            </div>

                            {/* Contact fields for Guest or pre-filled for User */}
                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 p-4 rounded-lg bg-stone-50 border border-stone-200">
                                <div>
                                    <InputLabel htmlFor="submitter_name" value="Your Full Name (Submitter) *" />
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
                                    <InputLabel htmlFor="submitter_email" value="Your Email Address (For Order Tracking) *" />
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

                            {/* Itemized Slips Breakdown */}
                            <div className="space-y-2">
                                <h3 className="font-cinzel text-xs font-bold text-liturgical-950 uppercase tracking-wider">
                                    Itemized Offering Slips Breakdown
                                </h3>
                                <div className="divide-y divide-stone-200 rounded-lg border border-stone-200 bg-stone-50/50 overflow-hidden text-xs">
                                    {data.intentions.map((item, idx) => {
                                        const cat = categories.find((c) => String(c.id) === String(item.category_id)) || categories[0] || {};
                                        const price = getSlipPrice(item.category_id);
                                        return (
                                            <div key={idx} className="p-3.5 flex justify-between items-center">
                                                <div>
                                                    <span className="font-bold text-stone-900 block">
                                                        Slip #{idx + 1}: {cat.label}
                                                    </span>
                                                    <span className="text-[11px] text-stone-500 block truncate max-w-md">
                                                        {item.person_or_family_name ? `Offered by: ${item.person_or_family_name}` : 'Intention entry'}
                                                    </span>
                                                </div>
                                                <span className="font-mono font-semibold text-stone-900">
                                                    {currency} {price.toFixed(2)}
                                                </span>
                                            </div>
                                        );
                                    })}
                                </div>
                            </div>

                            {/* Financial Summary */}
                            {!hasApprovedPricing ? (
                                <div className="p-4 rounded-lg bg-amber-50 border border-amber-300 text-xs text-amber-900 space-y-1">
                                    <div className="font-bold uppercase tracking-wider text-amber-800 flex items-center gap-1.5">
                                        <svg className="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                        </svg>
                                        Configuration Required
                                    </div>
                                    <p>
                                        No approved intention offering rate is currently active for this parish. Submissions are temporarily paused until configured by diocese administration.
                                    </p>
                                </div>
                            ) : (
                                <div className="p-4 rounded-lg bg-gold-50/70 border border-gold-300/80 space-y-2 text-xs">
                                    <span className="font-cinzel font-bold text-stone-900 uppercase tracking-wider block">
                                        Total Offering Calculation
                                    </span>
                                    <div className="flex justify-between py-1 border-b border-gold-200">
                                        <span>Total Slips Count:</span>
                                        <span className="font-semibold text-stone-800">{data.intentions.length} slip(s)</span>
                                    </div>
                                    <div className="flex justify-between py-1 border-b border-gold-200">
                                        <span>Intentions Subtotal (Arancel):</span>
                                        <span className="font-semibold font-mono text-stone-800">{currency} {totalCalculated}</span>
                                    </div>
                                    {feeActive && feeCfg && parseFloat(estimatedFee) > 0 && (
                                        <div className="flex justify-between py-1 border-b border-gold-200 text-stone-600">
                                            <span>{feeCfg.label || 'Platform Convenience Fee'}</span>
                                            <span className="font-semibold font-mono">{currency} {estimatedFee}</span>
                                        </div>
                                    )}
                                    <div className="flex justify-between py-1 text-sm font-bold text-liturgical-950">
                                        <span>Total Donation Amount:</span>
                                        <span className="font-mono text-base">
                                            {currency} {feeActive && feeCfg ? (parseFloat(totalCalculated) + parseFloat(estimatedFee)).toFixed(2) : totalCalculated}
                                        </span>
                                    </div>
                                    {feeActive && (
                                        <p className="text-[11px] text-stone-400 italic">
                                            * Indicative total. The confirmed amount is set at order creation.
                                        </p>
                                    )}
                                </div>
                            )}

                            {/* Privacy Acknowledged */}
                            <div className="flex items-start space-x-2 pt-2">
                                <input
                                    type="checkbox"
                                    id="privacy_acknowledged"
                                    checked={data.privacy_acknowledged}
                                    onChange={(e) => setData('privacy_acknowledged', e.target.checked)}
                                    className="mt-0.5 rounded border-stone-300 text-liturgical-900 focus:ring-gold-500 cursor-pointer"
                                    required
                                />
                                <label htmlFor="privacy_acknowledged" className="text-xs text-stone-600 leading-relaxed cursor-pointer">
                                    I acknowledge that the submitted names and spiritual intention wording will be recorded for Holy Mass and read publicly at the ambo during parish reading operations in accordance with parish privacy guidelines.
                                </label>
                            </div>
                            <InputError message={errors.privacy_acknowledged} />

                            <div className="flex justify-between items-center pt-4 border-t border-stone-100">
                                <button
                                    type="button"
                                    onClick={() => setStep(2)}
                                    className="text-xs font-semibold uppercase tracking-wider text-stone-600 hover:text-stone-900 cursor-pointer"
                                >
                                    &larr; Back to Slips
                                </button>

                                <PrimaryButton
                                    disabled={processing || !hasApprovedPricing}
                                    className="h-10 px-6 uppercase tracking-wider text-xs cursor-pointer font-bold"
                                >
                                    {processing ? 'Creating Order...' : 'Confirm & Proceed to GCash Payment &rarr;'}
                                </PrimaryButton>
                            </div>
                        </div>
                    )}
                </form>
            </main>
        </div>
    );
}
