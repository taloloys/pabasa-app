import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';

export default function ChurchPaymentSettingsEdit({ church, gcash = {} }) {
    const [previewUrl, setPreviewUrl] = useState(gcash.gcash_qr_url || null);

    const { data, setData, post, processing, errors, recentlySuccessful } = useForm({
        gcash_name:     gcash.gcash_name || '',
        gcash_number:   gcash.gcash_number || '',
        gcash_qr_image: null,
    });

    const handleFileChange = (e) => {
        const file = e.target.files[0];
        if (file) {
            setData('gcash_qr_image', file);
            setPreviewUrl(URL.createObjectURL(file));
        }
    };

    const submit = (e) => {
        e.preventDefault();
        post(route('church.payment-settings.update', { church: church.id }), {
            forceFormData: true,
        });
    };

    return (
        <AuthenticatedLayout
            header={
                <div>
                    <h1 className="font-cinzel text-xl font-bold text-liturgical-950">
                        GCash Payment Settings
                    </h1>
                    <p className="text-xs text-stone-500 mt-1">
                        Configure the parish GCash account displayed to parishioners on the payment screen.
                    </p>
                </div>
            }
        >
            <Head title={`Payment Settings — ${church.name}`} />

            <div className="py-8 max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
                <form onSubmit={submit} className="bg-white rounded-xl border border-gold-300/80 shadow-xs p-6 space-y-6" encType="multipart/form-data">
                    {recentlySuccessful && (
                        <div className="p-3 rounded-md bg-emerald-50 border border-emerald-200 text-xs text-emerald-800">
                            GCash payment settings updated successfully.
                        </div>
                    )}

                    <div>
                        <p className="text-xs text-stone-500 leading-relaxed bg-stone-50 border border-stone-200 rounded-lg px-3 py-2">
                            These details are displayed on the payment screen so parishioners know where to send their GCash offering.
                            Keep them accurate and up to date.
                        </p>
                    </div>

                    {/* GCash Account Name */}
                    <div>
                        <InputLabel htmlFor="gcash_name" value="GCash Account Name" />
                        <TextInput
                            id="gcash_name"
                            type="text"
                            className="mt-1 block w-full"
                            value={data.gcash_name}
                            onChange={(e) => setData('gcash_name', e.target.value)}
                            placeholder="e.g. Cebu Metropolitan Cathedral"
                        />
                        <p className="mt-1 text-[11px] text-stone-400">
                            The official registered name on your parish GCash account.
                        </p>
                        <InputError className="mt-1" message={errors.gcash_name} />
                    </div>

                    {/* GCash Mobile Number */}
                    <div>
                        <InputLabel htmlFor="gcash_number" value="GCash Mobile Number" />
                        <TextInput
                            id="gcash_number"
                            type="text"
                            className="mt-1 block w-full font-mono"
                            value={data.gcash_number}
                            onChange={(e) => setData('gcash_number', e.target.value)}
                            placeholder="e.g. 09171234567 or +639171234567"
                        />
                        <p className="mt-1 text-[11px] text-stone-400">
                            Philippine mobile format: 09xxxxxxxxxx or +639xxxxxxxxx
                        </p>
                        <InputError className="mt-1" message={errors.gcash_number} />
                    </div>

                    {/* GCash QR Code Upload */}
                    <div>
                        <InputLabel value="GCash QR Code Image" />
                        <div className="mt-2 space-y-3">
                            {/* Current / Preview */}
                            {previewUrl && (
                                <div className="flex items-start gap-4">
                                    <div className="border border-stone-200 rounded-lg p-2 bg-stone-50">
                                        <img
                                            src={previewUrl}
                                            alt="GCash QR Code Preview"
                                            className="h-36 w-36 object-contain rounded"
                                        />
                                    </div>
                                    <div className="text-xs text-stone-500 pt-2 space-y-1">
                                        <p className="font-semibold text-stone-700">
                                            {data.gcash_qr_image ? 'New QR Code (pending save)' : 'Current QR Code'}
                                        </p>
                                        <p>This image will be displayed prominently on the GCash payment screen for parishioners to scan.</p>
                                    </div>
                                </div>
                            )}

                            {/* Dropzone */}
                            <div className="relative flex justify-center px-6 pt-5 pb-6 border-2 border-dashed border-stone-300 rounded-lg hover:border-gold-400 transition cursor-pointer">
                                <div className="space-y-1 text-center">
                                    <svg className="mx-auto h-8 w-8 text-stone-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                        <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" strokeWidth={2} strokeLinecap="round" strokeLinejoin="round" />
                                    </svg>
                                    <div className="text-xs text-stone-600">
                                        <span className="font-semibold text-liturgical-900">
                                            {previewUrl ? 'Click to replace QR code' : 'Upload QR code image'}
                                        </span>
                                    </div>
                                    <p className="text-[11px] text-stone-500">PNG, JPG, WebP up to 2MB</p>
                                    <input
                                        type="file"
                                        accept="image/jpeg,image/png,image/webp"
                                        onChange={handleFileChange}
                                        className="sr-only"
                                        id="gcash_qr_image"
                                    />
                                    <label htmlFor="gcash_qr_image" className="absolute inset-0 cursor-pointer" />
                                </div>
                            </div>
                        </div>
                        <InputError className="mt-1" message={errors.gcash_qr_image} />
                    </div>

                    <div className="pt-4 border-t border-stone-100 flex items-center justify-end">
                        <PrimaryButton disabled={processing} className="cursor-pointer">
                            {processing ? 'Saving...' : 'Save Payment Settings'}
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </AuthenticatedLayout>
    );
}

