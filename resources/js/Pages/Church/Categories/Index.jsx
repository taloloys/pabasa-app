import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import DangerButton from '@/Components/DangerButton';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';

export default function Index({ church, categories }) {
    const [confirmingDeactivation, setConfirmingDeactivation] = useState(false);
    const [selectedCategory, setSelectedCategory] = useState(null);
    const [deactivationReason, setDeactivationReason] = useState('');

    const { data, setData, post, processing, errors, reset } = useForm({
        label: '',
        key: '',
        type: 'intention',
        description: '',
        color: '#7e46c4',
        sort_order: 0,
    });

    const submitCreate = (e) => {
        e.preventDefault();
        post(route('church.categories.store', { church: church.id }), {
            onSuccess: () => reset(),
        });
    };

    const handleToggleClick = (category) => {
        if (category.is_active) {
            setSelectedCategory(category);
            setDeactivationReason('');
            setConfirmingDeactivation(true);
        } else {
            router.post(
                route('church.categories.toggle', { church: church.id, category: category.id }),
                {},
                { preserveScroll: true }
            );
        }
    };

    const executeDeactivation = () => {
        if (!selectedCategory) return;

        router.post(
            route('church.categories.toggle', { church: church.id, category: selectedCategory.id }),
            { reason: deactivationReason },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setConfirmingDeactivation(false);
                    setSelectedCategory(null);
                },
            }
        );
    };

    return (
        <AuthenticatedLayout
            header={
                <div>
                    <h1 className="font-cinzel text-xl font-bold text-liturgical-950">
                        Intention & Mass Categories
                    </h1>
                    <p className="text-xs text-stone-500 mt-1">
                        Manage church-specific intention classifications. Inactive categories prevent new submissions while preserving historical intention records.
                    </p>
                </div>
            }
        >
            <Head title={`Categories — ${church.name}`} />

            <div className="py-8">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
                    {/* Add Category Form */}
                    <div className="bg-white rounded-xl border border-gold-300/80 shadow-xs p-6">
                        <h2 className="font-cinzel text-base font-bold text-liturgical-950 mb-4">
                            Create New Category
                        </h2>

                        <form onSubmit={submitCreate} className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                            <div>
                                <InputLabel htmlFor="label" value="Category Label *" />
                                <TextInput
                                    id="label"
                                    type="text"
                                    className="mt-1 block w-full text-xs"
                                    value={data.label}
                                    onChange={(e) => setData('label', e.target.value)}
                                    placeholder="e.g. Thanksgiving, Souls in Purgatory"
                                    required
                                />
                                <InputError className="mt-1" message={errors.label} />
                            </div>

                            <div>
                                <InputLabel htmlFor="type" value="Classification Type *" />
                                <select
                                    id="type"
                                    value={data.type}
                                    onChange={(e) => setData('type', e.target.value)}
                                    className="mt-1 block w-full rounded-md border-stone-300 shadow-2xs text-xs focus:border-gold-500 focus:ring-gold-500"
                                >
                                    <option value="intention">Intention Category</option>
                                    <option value="mass">Mass Schedule Category</option>
                                </select>
                                <InputError className="mt-1" message={errors.type} />
                            </div>

                            <div>
                                <InputLabel htmlFor="color" value="Liturgical Color Tag" />
                                <div className="mt-1 flex items-center space-x-2">
                                    <input
                                        type="color"
                                        id="color"
                                        value={data.color}
                                        onChange={(e) => setData('color', e.target.value)}
                                        className="h-9 w-12 rounded border border-stone-300 cursor-pointer p-0.5"
                                    />
                                    <TextInput
                                        type="text"
                                        value={data.color}
                                        onChange={(e) => setData('color', e.target.value)}
                                        className="w-24 text-xs uppercase"
                                    />
                                </div>
                                <InputError className="mt-1" message={errors.color} />
                            </div>

                            <div className="flex items-end">
                                <PrimaryButton disabled={processing} className="w-full justify-center h-9 text-xs cursor-pointer">
                                    {processing ? 'Creating...' : '+ Add Category'}
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>

                    {/* Categories Table */}
                    <div className="bg-white rounded-xl border border-gold-300/80 shadow-xs overflow-hidden">
                        <div className="p-4 border-b border-stone-100 flex items-center justify-between">
                            <h2 className="font-cinzel text-base font-bold text-liturgical-950">
                                Defined Categories ({categories.length})
                            </h2>
                            <span className="text-xs text-stone-500">
                                Deactivation is auditable and non-destructive.
                            </span>
                        </div>

                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-xs text-stone-600">
                                <thead className="bg-stone-50 text-[11px] uppercase tracking-wider text-stone-500 border-b border-stone-200">
                                    <tr>
                                        <th className="py-3 px-4">Category</th>
                                        <th className="py-3 px-4">Type</th>
                                        <th className="py-3 px-4">Scope</th>
                                        <th className="py-3 px-4">Status</th>
                                        <th className="py-3 px-4 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-stone-100">
                                    {categories.map((cat) => (
                                        <tr key={cat.id} className="hover:bg-gold-50/30 transition">
                                            <td className="py-3 px-4 flex items-center space-x-2.5">
                                                <span
                                                    className="w-3.5 h-3.5 rounded-full shrink-0 border border-black/10"
                                                    style={{ backgroundColor: cat.color || '#7e46c4' }}
                                                />
                                                <div>
                                                    <span className="font-semibold text-stone-900 block">{cat.label}</span>
                                                    <span className="text-[11px] text-stone-400 font-mono">{cat.key}</span>
                                                </div>
                                            </td>

                                            <td className="py-3 px-4">
                                                <span className="inline-flex px-2 py-0.5 rounded text-[10px] font-medium uppercase tracking-wider bg-stone-100 text-stone-700">
                                                    {cat.type}
                                                </span>
                                            </td>

                                            <td className="py-3 px-4">
                                                {cat.is_system ? (
                                                    <span className="inline-flex px-2 py-0.5 rounded text-[10px] font-medium bg-purple-50 text-purple-700 border border-purple-200">
                                                        System-Wide
                                                    </span>
                                                ) : (
                                                    <span className="inline-flex px-2 py-0.5 rounded text-[10px] font-medium bg-gold-50 text-gold-800 border border-gold-200">
                                                        Parish-Specific
                                                    </span>
                                                )}
                                            </td>

                                            <td className="py-3 px-4">
                                                {cat.is_active ? (
                                                    <span className="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                        Active
                                                    </span>
                                                ) : (
                                                    <span className="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-stone-100 text-stone-500 border border-stone-200">
                                                        Deactivated
                                                    </span>
                                                )}
                                            </td>

                                            <td className="py-3 px-4 text-right">
                                                <button
                                                    onClick={() => handleToggleClick(cat)}
                                                    className={`px-3 py-1 rounded text-xs font-semibold uppercase tracking-wider transition cursor-pointer ${
                                                        cat.is_active
                                                            ? 'border border-amber-300 bg-amber-50 text-amber-800 hover:bg-amber-100'
                                                            : 'border border-emerald-300 bg-emerald-50 text-emerald-800 hover:bg-emerald-100'
                                                    }`}
                                                >
                                                    {cat.is_active ? 'Deactivate' : 'Reactivate'}
                                                </button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {/* Deactivation Warning Modal */}
            <Modal show={confirmingDeactivation} onClose={() => setConfirmingDeactivation(false)}>
                <div className="p-6">
                    <h2 className="font-cinzel text-lg font-bold text-liturgical-950">
                        Deactivate Intention Category: {selectedCategory?.label}
                    </h2>

                    <p className="mt-2 text-xs text-stone-600 leading-relaxed">
                        Deactivating this category will immediately prevent parishioners from selecting it for new prayer intentions.
                        All historical intentions and reading list archives associated with this category will remain completely intact.
                    </p>

                    <div className="mt-4">
                        <InputLabel htmlFor="deactivation_reason" value="Reason for Deactivation (Audited)" />
                        <textarea
                            id="deactivation_reason"
                            rows={2}
                            value={deactivationReason}
                            onChange={(e) => setDeactivationReason(e.target.value)}
                            className="mt-1 block w-full rounded-md border-stone-300 shadow-2xs text-xs"
                            placeholder="e.g. Consolidated with General Thanksgiving category"
                        />
                    </div>

                    <div className="mt-6 flex justify-end space-x-3">
                        <SecondaryButton onClick={() => setConfirmingDeactivation(false)} className="cursor-pointer">
                            Cancel
                        </SecondaryButton>

                        <DangerButton onClick={executeDeactivation} className="cursor-pointer">
                            Confirm Deactivation
                        </DangerButton>
                    </div>
                </div>
            </Modal>
        </AuthenticatedLayout>
    );
}

