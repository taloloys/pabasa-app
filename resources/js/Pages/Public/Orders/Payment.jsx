import ApplicationLogo from '@/Components/ApplicationLogo';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { Head, Link, useForm } from '@inertiajs/react';
import { useState } from 'react';

export default function Payment({ order, church, mass, payment, guestToken }) {
    const [previewUrl, setPreviewUrl] = useState(null);

    const { data, setData, post, processing, errors } = useForm({
        reference_number: '',
        submitted_amount: order.expected_amount || '',
        evidence_file: null,
    });

    const handleFileChange = (e) => {
        const file = e.target.files[0];
        if (file) {
            setData('evidence_file', file);
            setPreviewUrl(URL.createObjectURL(file));
        }
    };

    const submit = (e) => {
        e.preventDefault();
        post(route('orders.evidence', { order: order.id }));
    };

    const isSubmitted = payment?.status === 'pending_verification' || payment?.status === 'needs_review' || payment?.status === 'verified';

    return (
        <div className="min-h-screen bg-gradient-to-b from-[#FDFBF7] via-[#FAF6EE] to-[#F5ECE0] text-stone-800 antialiased selection:bg-liturgical-800 selection:text-gold-200">
            <Head title={`GCash Offering — ${order.order_number}`} />

            <header className="border-b border-gold-300/60 bg-white/95 backdrop-blur-md sticky top-0 z-40 shadow-2xs">
                <div className="h-1 bg-gradient-to-r from-liturgical-900 via-gold-500 to-liturgical-900" />
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                    <Link href="/" className="flex items-center space-x-2">
                        <ApplicationLogo className="h-10 w-10 shadow-xs" size="sm" />
                    </Link>

                    <div className="flex items-center space-x-2 text-xs">
                        <span className="text-stone-500">Order:</span>
                        <span className="font-mono font-bold text-liturgical-950">{order.order_number}</span>
                    </div>
                </div>
            </header>

            <main className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-6">
                {/* Guest tracking alert banner */}
                {guestToken && (
                    <div className="p-4 rounded-xl bg-purple-50 border border-purple-200 text-xs text-purple-900 space-y-2">
                        <div className="flex items-center space-x-2">
                            <svg className="w-5 h-5 text-purple-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span className="font-bold text-sm">Guest Tracking Access</span>
                        </div>
                        <p>
                            Bookmark your secure tracking link to view intention status, submit changes, and download your confirmation receipt:
                        </p>
                        <div className="pt-1">
                            <Link
                                href={route('guest.orders.show', { token: guestToken })}
                                className="inline-flex items-center px-3 py-1.5 rounded bg-purple-700 text-white hover:bg-purple-800 text-xs font-semibold tracking-wider transition"
                            >
                                Open My Guest Tracking Portal &rarr;
                            </Link>
                        </div>
                    </div>
                )}

                {/* Main Payment Offering Card */}
                <div className="bg-white rounded-xl border border-gold-300/80 shadow-xs overflow-hidden">
                    <div className="p-6 sm:p-8 border-b border-stone-100 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                        <div>
                            <span className="text-xs uppercase font-bold text-gold-700 tracking-wider block">
                                Step 4 &bull; GCash Offering
                            </span>
                            <h1 className="font-cinzel text-2xl font-bold text-liturgical-950 mt-1">
                                Complete Your Prayer Offering
                            </h1>
                            <p className="text-xs text-stone-500 mt-0.5">
                                Intention Mass: {mass.title} ({mass.starts_at_formatted})
                            </p>
                        </div>

                        <div className="text-right">
                            <span className="text-[11px] text-stone-500 block uppercase font-medium">Expected Offering</span>
                            <span className="font-bold text-2xl text-liturgical-950 block">
                                {order.currency} {order.expected_amount}
                            </span>
                        </div>
                    </div>

                    <div className="p-6 sm:p-8 grid grid-cols-1 md:grid-cols-2 gap-8">
                        {/* Left: GCash Instructions */}
                        <div className="space-y-4 text-xs text-stone-600">
                            <h2 className="font-cinzel text-base font-bold text-liturgical-950">
                                How to Pay via GCash:
                            </h2>

                            <ol className="list-decimal pl-4 space-y-2 leading-relaxed">
                                <li>Open your GCash application on your mobile device.</li>
                                <li>
                                    Send offering to the verified parish GCash account:
                                    <span className="font-bold text-stone-900 block mt-0.5">
                                        Account: {church.name}
                                    </span>
                                    <span className="font-mono font-bold text-liturgical-900 block">
                                        Number: {church.contact_phone || '0917-123-4567'}
                                    </span>
                                </li>
                                <li>Enter the exact amount: <span className="font-bold text-stone-900">{order.currency} {order.expected_amount}</span>.</li>
                                <li>Take a clear screenshot of the confirmed transaction screen showing the reference number and date.</li>
                                <li>Upload the screenshot in the form on the right.</li>
                            </ol>

                            <div className="p-3 rounded-md bg-stone-50 border border-stone-200 text-[11px] text-stone-500">
                                <span className="font-semibold text-stone-700 block">Solemn Verification Notice:</span>
                                All GCash submissions are reviewed and verified by Central Administration staff before reading lists are finalized.
                            </div>
                        </div>

                        {/* Right: Upload Form */}
                        <div>
                            {isSubmitted ? (
                                <div className="p-6 rounded-xl bg-emerald-50 border border-emerald-200 text-center space-y-3">
                                    <div className="w-12 h-12 mx-auto rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center">
                                        <svg className="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>
                                    <h3 className="font-cinzel text-base font-bold text-emerald-950">
                                        Offering Under Review
                                    </h3>
                                    <p className="text-xs text-emerald-800 leading-relaxed">
                                        We have received your GCash transaction screenshot with reference <span className="font-mono font-bold">{payment?.submitted_reference}</span>. Central Admin will verify your payment.
                                    </p>
                                </div>
                            ) : (
                                <form onSubmit={submit} className="space-y-4">
                                    <div>
                                        <InputLabel htmlFor="reference_number" value="GCash Reference Number *" />
                                        <TextInput
                                            id="reference_number"
                                            type="text"
                                            className="mt-1 block w-full text-xs font-mono uppercase tracking-wider"
                                            value={data.reference_number}
                                            onChange={(e) => setData('reference_number', e.target.value)}
                                            placeholder="e.g. 1002 9384 1928"
                                            required
                                        />
                                        <InputError message={errors.reference_number} />
                                    </div>

                                    <div>
                                        <InputLabel htmlFor="submitted_amount" value="Amount Sent (PHP) *" />
                                        <TextInput
                                            id="submitted_amount"
                                            type="number"
                                            step="0.01"
                                            className="mt-1 block w-full text-xs"
                                            value={data.submitted_amount}
                                            onChange={(e) => setData('submitted_amount', e.target.value)}
                                            required
                                        />
                                        <InputError message={errors.submitted_amount} />
                                    </div>

                                    <div>
                                        <InputLabel value="GCash Screenshot (JPEG, PNG, WebP &le; 5MB) *" />
                                        <div className="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-dashed border-stone-300 rounded-lg hover:border-gold-400 transition cursor-pointer">
                                            <div className="space-y-1 text-center">
                                                {previewUrl ? (
                                                    <div className="space-y-2">
                                                        <img src={previewUrl} alt="Screenshot Preview" className="max-h-40 mx-auto rounded shadow-xs" />
                                                        <p className="text-xs text-stone-500">Click to change screenshot</p>
                                                    </div>
                                                ) : (
                                                    <>
                                                        <svg className="mx-auto h-10 w-10 text-stone-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                                            <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" strokeWidth={2} strokeLinecap="round" strokeLinejoin="round" />
                                                        </svg>
                                                        <div className="flex text-xs text-stone-600 justify-center">
                                                            <span className="font-semibold text-liturgical-900">Upload screenshot</span>
                                                        </div>
                                                        <p className="text-[11px] text-stone-500">PNG, JPG, WebP up to 5MB</p>
                                                    </>
                                                )}
                                                <input
                                                    type="file"
                                                    accept="image/jpeg,image/png,image/webp"
                                                    onChange={handleFileChange}
                                                    className="sr-only"
                                                    id="file-upload"
                                                    required={!data.evidence_file}
                                                />
                                                <label htmlFor="file-upload" className="absolute inset-0 cursor-pointer" />
                                            </div>
                                        </div>
                                        <InputError message={errors.evidence_file} />
                                    </div>

                                    <PrimaryButton disabled={processing || !data.evidence_file} className="w-full justify-center text-xs cursor-pointer">
                                        {processing ? 'Uploading Evidence...' : 'Submit Payment Screenshot'}
                                    </PrimaryButton>
                                </form>
                            )}
                        </div>
                    </div>
                </div>
            </main>
        </div>
    );
}

