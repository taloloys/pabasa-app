import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { Head, useForm } from '@inertiajs/react';

export default function Edit({ church, branding }) {
    const { data, setData, patch, processing, errors, recentlySuccessful } = useForm({
        logo_path: branding?.logo_path || '',
        banner_path: branding?.banner_path || '',
        theme_color: branding?.theme_color || '#7e46c4',
        public_description: branding?.public_description || '',
        contact_email: branding?.contact_email || church.contact_email || '',
        contact_phone: branding?.contact_phone || church.contact_phone || '',
    });

    const submit = (e) => {
        e.preventDefault();
        patch(route('church.branding.update', { church: church.id }));
    };

    return (
        <AuthenticatedLayout
            header={
                <div>
                    <h1 className="font-cinzel text-xl font-bold text-liturgical-950">
                        Parish Branding & Presentation
                    </h1>
                    <p className="text-xs text-stone-500 mt-1">
                        Configure public appearance and contact information for {church.name}.
                    </p>
                </div>
            }
        >
            <Head title={`Branding — ${church.name}`} />

            <div className="py-8">
                <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                    <form onSubmit={submit} className="bg-white rounded-xl border border-gold-300/80 shadow-xs p-6 space-y-6">
                        {recentlySuccessful && (
                            <div className="p-3 rounded-md bg-emerald-50 border border-emerald-200 text-xs text-emerald-800">
                                Parish presentation and branding settings updated successfully.
                            </div>
                        )}

                        {/* Public Description */}
                        <div>
                            <InputLabel htmlFor="public_description" value="Public Parish Description / Motto" />
                            <textarea
                                id="public_description"
                                rows={3}
                                className="mt-1 block w-full rounded-md border-stone-300 shadow-2xs focus:border-gold-500 focus:ring-gold-500 text-sm"
                                value={data.public_description}
                                onChange={(e) => setData('public_description', e.target.value)}
                                placeholder="Enter a brief welcome or solemn spiritual message displayed on your public parish page..."
                            />
                            <InputError className="mt-1" message={errors.public_description} />
                        </div>

                        {/* Theme Color & Accent */}
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div>
                                <InputLabel htmlFor="theme_color" value="Liturgical Accent Color" />
                                <div className="mt-1 flex items-center space-x-3">
                                    <input
                                        type="color"
                                        id="theme_color"
                                        value={data.theme_color}
                                        onChange={(e) => setData('theme_color', e.target.value)}
                                        className="h-10 w-16 p-1 rounded border border-stone-300 cursor-pointer"
                                    />
                                    <TextInput
                                        type="text"
                                        value={data.theme_color}
                                        onChange={(e) => setData('theme_color', e.target.value)}
                                        className="w-32 text-xs uppercase"
                                    />
                                </div>
                                <InputError className="mt-1" message={errors.theme_color} />
                            </div>

                            {/* Contact Details */}
                            <div>
                                <InputLabel htmlFor="contact_email" value="Public Inquiries Email" />
                                <TextInput
                                    id="contact_email"
                                    type="email"
                                    className="mt-1 block w-full"
                                    value={data.contact_email}
                                    onChange={(e) => setData('contact_email', e.target.value)}
                                    placeholder="parish.office@example.org"
                                />
                                <InputError className="mt-1" message={errors.contact_email} />
                            </div>
                        </div>

                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div>
                                <InputLabel htmlFor="contact_phone" value="Parish Office Telephone / Mobile" />
                                <TextInput
                                    id="contact_phone"
                                    type="text"
                                    className="mt-1 block w-full"
                                    value={data.contact_phone}
                                    onChange={(e) => setData('contact_phone', e.target.value)}
                                    placeholder="+63 917 123 4567"
                                />
                                <InputError className="mt-1" message={errors.contact_phone} />
                            </div>

                            <div>
                                <InputLabel htmlFor="logo_path" value="Logo Storage Key / URL" />
                                <TextInput
                                    id="logo_path"
                                    type="text"
                                    className="mt-1 block w-full"
                                    value={data.logo_path}
                                    onChange={(e) => setData('logo_path', e.target.value)}
                                    placeholder="branding/logos/parish-logo.png"
                                />
                                <InputError className="mt-1" message={errors.logo_path} />
                            </div>
                        </div>

                        <div className="pt-4 border-t border-stone-100 flex items-center justify-end">
                            <PrimaryButton disabled={processing} className="cursor-pointer">
                                {processing ? 'Saving Changes...' : 'Save Presentation'}
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

