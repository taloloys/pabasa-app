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

    // State for editing category and custom slip settings
    const [editingCategory, setEditingCategory] = useState(null);
    const [isSaving, setIsSaving] = useState(false);
    const [editErrors, setEditErrors] = useState({});

    // Local state for the edit form
    const [editData, setEditData] = useState({
        label: '',
        description: '',
        color: '#7e46c4',
        sort_order: 0,
        template_type: 'standard',
        price: '150.00',
        occasions_text: '',
        max_souls: 10,
        special_notes: '',
    });

    // Create Category Form
    const {
        data: createData,
        setData: setCreateData,
        post: postCreate,
        processing: createProcessing,
        errors: createErrors,
        reset: resetCreate,
    } = useForm({
        label: '',
        key: '',
        type: 'intention',
        template_type: 'standard',
        price: '150.00',
        description: '',
        color: '#7e46c4',
        sort_order: 0,
    });

    const submitCreate = (e) => {
        e.preventDefault();
        postCreate(route('church.categories.store', { church: church.id }), {
            onSuccess: () => resetCreate(),
        });
    };

    const openEditModal = (cat) => {
        setEditingCategory(cat);
        setEditErrors({});

        const config = cat.form_config || {};
        let occasions = config.occasions;
        if (!occasions || !Array.isArray(occasions)) {
            occasions = [
                'Birthday Anniversary',
                'Wedding Anniversary',
                'Anniversary',
                'Passing the Exam',
                'New Job/Promotion',
                'Recovery/ Good health',
                'Graces & Blessing received',
                'Good Harvest',
            ];
        }

        let notesText = '';
        if (config.notes) {
            notesText = Array.isArray(config.notes) ? config.notes.join('\n') : String(config.notes);
        } else {
            notesText = "1. We do not pay for the mass. Your donation (Arancel) is for the Parish Church operational expenses and evangelization programs.\n2. The inclusion in the intention does not substitute for the donor's presence in the Holy Mass.\n3. Write your intentions legibly.";
        }

        setEditData({
            label: cat.label || '',
            description: cat.description || '',
            color: cat.color || '#7e46c4',
            sort_order: cat.sort_order ?? 0,
            template_type: cat.template_type || 'standard',
            price: cat.price ? parseFloat(cat.price).toFixed(2) : '150.00',
            max_souls: config.max_souls || 10,
            occasions_text: occasions.join('\n'),
            special_notes: notesText,
        });
    };

    const submitEdit = (e) => {
        e.preventDefault();
        if (!editingCategory) return;

        setIsSaving(true);
        setEditErrors({});

        // Build form_config payload based on template_type
        let finalConfig = {};
        if (editData.template_type === 'souls') {
            finalConfig = { max_souls: parseInt(editData.max_souls, 10) || 10 };
        } else if (editData.template_type === 'thanksgiving') {
            const lines = (editData.occasions_text || '')
                .split('\n')
                .map((l) => l.trim())
                .filter((l) => l.length > 0);
            finalConfig = { occasions: lines };
        } else if (editData.template_type === 'special') {
            finalConfig = { notes: editData.special_notes };
        }

        router.patch(
            route('church.categories.update', { church: church.id, category: editingCategory.id }),
            {
                label: editData.label,
                description: editData.description,
                color: editData.color,
                sort_order: editData.sort_order,
                template_type: editData.template_type,
                price: editData.price,
                form_config: finalConfig,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setIsSaving(false);
                    setEditingCategory(null);
                },
                onError: (errs) => {
                    setIsSaving(false);
                    setEditErrors(errs || {});
                },
            }
        );
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

    const getTemplateBadge = (cat) => {
        const type = cat.template_type || 'standard';
        switch (type) {
            case 'thanksgiving':
                return (
                    <span className="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200">
                        Thanksgiving Form (Checkboxes)
                    </span>
                );
            case 'souls':
                const max = cat.form_config?.max_souls || 10;
                return (
                    <span className="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-indigo-50 text-indigo-800 border border-indigo-200">
                        Souls List (Max: {max})
                    </span>
                );
            case 'special':
                return (
                    <span className="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-amber-50 text-amber-800 border border-amber-200">
                        Special Intention (Ruled + Note)
                    </span>
                );
            default:
                return (
                    <span className="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-stone-100 text-stone-700 border border-stone-200">
                        Standard Form
                    </span>
                );
        }
    };

    return (
        <AuthenticatedLayout
            header={
                <div>
                    <h1 className="font-cinzel text-xl font-bold text-liturgical-950">
                        Intention & Mass Services (Slips)
                    </h1>
                    <p className="text-xs text-stone-500 mt-1">
                        Customize parish slips, offering amounts (Arancel rate), and form contents like celebration checkboxes and soul count limits.
                    </p>
                </div>
            }
        >
            <Head title={`Categories & Slips — ${church.name}`} />

            <div className="py-8">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
                    {/* Add Category Form */}
                    <div className="bg-white rounded-xl border border-gold-300/80 shadow-xs p-6">
                        <h2 className="font-cinzel text-base font-bold text-liturgical-950 mb-4">
                            Create New Category / Slip Service
                        </h2>

                        <form onSubmit={submitCreate} className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                            <div>
                                <InputLabel htmlFor="label" value="Category Label *" />
                                <TextInput
                                    id="label"
                                    type="text"
                                    className="mt-1 block w-full text-xs"
                                    value={createData.label}
                                    onChange={(e) => setCreateData('label', e.target.value)}
                                    placeholder="e.g. Thanksgiving Mass"
                                    required
                                />
                                <InputError className="mt-1" message={createErrors.label} />
                            </div>

                            <div>
                                <InputLabel htmlFor="type" value="Classification Type *" />
                                <select
                                    id="type"
                                    value={createData.type}
                                    onChange={(e) => setCreateData('type', e.target.value)}
                                    className="mt-1 block w-full rounded-md border-stone-300 shadow-2xs text-xs focus:border-gold-500 focus:ring-gold-500"
                                >
                                    <option value="intention">Intention Offering Slip</option>
                                    <option value="mass">Mass Schedule Category</option>
                                </select>
                                <InputError className="mt-1" message={createErrors.type} />
                            </div>

                            <div>
                                <InputLabel htmlFor="template_type" value="Slip Form Template" />
                                <select
                                    id="template_type"
                                    value={createData.template_type}
                                    onChange={(e) => setCreateData('template_type', e.target.value)}
                                    className="mt-1 block w-full rounded-md border-stone-300 shadow-2xs text-xs focus:border-gold-500 focus:ring-gold-500"
                                >
                                    <option value="standard">Standard Freeform</option>
                                    <option value="thanksgiving">Thanksgiving Mass (Checkboxes)</option>
                                    <option value="souls">Mass for the Souls (Numbered List)</option>
                                    <option value="special">Special Mass Intention (Ruled + Note)</option>
                                </select>
                                <InputError className="mt-1" message={createErrors.template_type} />
                            </div>

                            <div>
                                <InputLabel htmlFor="price" value="Offering Rate (PHP ₱)" />
                                <TextInput
                                    id="price"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    className="mt-1 block w-full text-xs"
                                    value={createData.price}
                                    onChange={(e) => setCreateData('price', e.target.value)}
                                    placeholder="150.00"
                                />
                                <InputError className="mt-1" message={createErrors.price} />
                            </div>

                            <div className="flex items-end">
                                <PrimaryButton disabled={createProcessing} className="w-full justify-center h-9 text-xs cursor-pointer">
                                    {createProcessing ? 'Creating...' : '+ Add Category'}
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>

                    {/* Categories Table */}
                    <div className="bg-white rounded-xl border border-gold-300/80 shadow-xs overflow-hidden">
                        <div className="p-4 border-b border-stone-100 flex items-center justify-between">
                            <div>
                                <h2 className="font-cinzel text-base font-bold text-liturgical-950">
                                    Configured Parish Slips & Categories ({categories.length})
                                </h2>
                                <p className="text-[11px] text-stone-500 mt-0.5">
                                    Customize templates (e.g. 7 or 10 souls, thanksgiving occasions) and custom offering rates per slip.
                                </p>
                            </div>
                            <span className="text-xs text-stone-500">
                                Deactivation is auditable and non-destructive.
                            </span>
                        </div>

                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-xs text-stone-600">
                                <thead className="bg-stone-50 text-[11px] uppercase tracking-wider text-stone-500 border-b border-stone-200">
                                    <tr>
                                        <th className="py-3 px-4">Service / Slip Category</th>
                                        <th className="py-3 px-4">Template & Form</th>
                                        <th className="py-3 px-4">Offering Rate</th>
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
                                                {getTemplateBadge(cat)}
                                            </td>

                                            <td className="py-3 px-4">
                                                <span className="font-semibold text-stone-900 font-mono">
                                                    ₱ {cat.price ? parseFloat(cat.price).toFixed(2) : '150.00'}
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

                                            <td className="py-3 px-4 text-right space-x-2">
                                                <button
                                                    type="button"
                                                    onClick={() => openEditModal(cat)}
                                                    className="px-2.5 py-1 rounded border border-gold-400 bg-gold-50 text-gold-900 hover:bg-gold-100 text-xs font-semibold uppercase tracking-wider transition cursor-pointer"
                                                >
                                                    Edit & Customize
                                                </button>

                                                <button
                                                    type="button"
                                                    onClick={() => handleToggleClick(cat)}
                                                    className={`px-2.5 py-1 rounded text-xs font-semibold uppercase tracking-wider transition cursor-pointer ${
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

            {/* Edit Category & Custom Slip Modal */}
            <Modal show={Boolean(editingCategory)} onClose={() => setEditingCategory(null)} maxWidth="2xl">
                {editingCategory && (
                    <form onSubmit={submitEdit} className="p-6 space-y-5">
                        <div className="border-b border-stone-200 pb-3 flex items-center justify-between">
                            <div>
                                <h2 className="font-cinzel text-lg font-bold text-liturgical-950">
                                    Customize Slip Service: {editingCategory.label}
                                </h2>
                                <p className="text-xs text-stone-500 mt-0.5">
                                    Configure the slip template, offering rate, and user input options.
                                </p>
                            </div>
                            <button
                                type="button"
                                onClick={() => setEditingCategory(null)}
                                className="text-stone-400 hover:text-stone-600 text-base font-bold cursor-pointer"
                            >
                                &times;
                            </button>
                        </div>

                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <InputLabel htmlFor="edit_label" value="Category / Slip Label *" />
                                <TextInput
                                    id="edit_label"
                                    type="text"
                                    className="mt-1 block w-full text-xs"
                                    value={editData.label}
                                    onChange={(e) => setEditData({ ...editData, label: e.target.value })}
                                    required
                                />
                                <InputError message={editErrors.label} />
                            </div>

                            <div>
                                <InputLabel htmlFor="edit_price" value="Offering Rate per Slip (PHP ₱) *" />
                                <TextInput
                                    id="edit_price"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    className="mt-1 block w-full text-xs font-mono font-semibold"
                                    value={editData.price}
                                    onChange={(e) => setEditData({ ...editData, price: e.target.value })}
                                    required
                                />
                                <InputError message={editErrors.price} />
                            </div>
                        </div>

                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <InputLabel htmlFor="edit_template" value="Slip Form Template *" />
                                <select
                                    id="edit_template"
                                    value={editData.template_type}
                                    onChange={(e) => setEditData({ ...editData, template_type: e.target.value })}
                                    className="mt-1 block w-full rounded-md border-stone-300 shadow-2xs text-xs focus:border-gold-500 focus:ring-gold-500"
                                >
                                    <option value="standard">Standard Freeform</option>
                                    <option value="thanksgiving">Thanksgiving Mass (Celebration Checkboxes)</option>
                                    <option value="souls">Mass for the Souls (Numbered Lines)</option>
                                    <option value="special">Special Mass Intention (Ruled + Arancel Notice)</option>
                                </select>
                                <InputError message={editErrors.template_type} />
                            </div>

                            <div>
                                <InputLabel htmlFor="edit_color" value="Liturgical Accent Color" />
                                <div className="mt-1 flex items-center space-x-2">
                                    <input
                                        type="color"
                                        id="edit_color"
                                        value={editData.color}
                                        onChange={(e) => setEditData({ ...editData, color: e.target.value })}
                                        className="h-9 w-12 rounded border border-stone-300 cursor-pointer p-0.5"
                                    />
                                    <TextInput
                                        type="text"
                                        value={editData.color}
                                        onChange={(e) => setEditData({ ...editData, color: e.target.value })}
                                        className="w-28 text-xs uppercase"
                                    />
                                </div>
                            </div>
                        </div>

                        {/* Template-Specific Configuration Section */}
                        <div className="bg-stone-50 border border-gold-200/80 rounded-lg p-4 space-y-3">
                            <h3 className="font-semibold text-xs text-liturgical-950 uppercase tracking-wider">
                                Slip Contents & Customization
                            </h3>

                            {editData.template_type === 'souls' && (
                                <div>
                                    <InputLabel htmlFor="max_souls" value="Maximum Souls Allowed per Slip (e.g. 7 or 10) *" />
                                    <TextInput
                                        id="max_souls"
                                        type="number"
                                        min="1"
                                        max="50"
                                        className="mt-1 block w-32 text-xs font-mono"
                                        value={editData.max_souls}
                                        onChange={(e) => setEditData({ ...editData, max_souls: e.target.value })}
                                        required
                                    />
                                    <p className="text-[11px] text-stone-500 mt-1">
                                        For Mother of the Redeemer Parish: set to <strong>10</strong> (or <strong>7</strong>) to match the printed slip lines.
                                    </p>
                                </div>
                            )}

                            {editData.template_type === 'thanksgiving' && (
                                <div>
                                    <InputLabel htmlFor="occasions_text" value="Occasion Checkboxes (One per line) *" />
                                    <textarea
                                        id="occasions_text"
                                        rows={6}
                                        className="mt-1 block w-full rounded-md border-stone-300 shadow-2xs text-xs font-mono"
                                        value={editData.occasions_text}
                                        onChange={(e) => setEditData({ ...editData, occasions_text: e.target.value })}
                                        placeholder="Birthday Anniversary&#10;Wedding Anniversary&#10;Passing the Exam"
                                    />
                                    <p className="text-[11px] text-stone-500 mt-1">
                                        These options appear as checkboxes on the parishioner Thanksgiving Mass slip form.
                                    </p>
                                </div>
                            )}

                            {editData.template_type === 'special' && (
                                <div>
                                    <InputLabel htmlFor="special_notes" value="Parish Arancel & Presence Notes (Shown on slip)" />
                                    <textarea
                                        id="special_notes"
                                        rows={4}
                                        className="mt-1 block w-full rounded-md border-stone-300 shadow-2xs text-xs"
                                        value={editData.special_notes}
                                        onChange={(e) => setEditData({ ...editData, special_notes: e.target.value })}
                                        placeholder="1. We do not pay for the mass..."
                                    />
                                    <p className="text-[11px] text-stone-500 mt-1">
                                        Rendered verbatim under the intention text area, exactly matching the paper slip notice.
                                    </p>
                                </div>
                            )}

                            {editData.template_type === 'standard' && (
                                <p className="text-xs text-stone-500 italic">
                                    Standard template provides a freeform intention text box and person/family name input.
                                </p>
                            )}

                            {editErrors.form_config && (
                                <InputError message={editErrors.form_config} />
                            )}
                        </div>

                        <div className="flex justify-end space-x-3 pt-3 border-t border-stone-200">
                            <SecondaryButton type="button" onClick={() => setEditingCategory(null)} className="cursor-pointer">
                                Cancel
                            </SecondaryButton>
                            <PrimaryButton disabled={isSaving} className="cursor-pointer">
                                {isSaving ? 'Saving Changes...' : 'Save Slip Settings'}
                            </PrimaryButton>
                        </div>
                    </form>
                )}
            </Modal>

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
