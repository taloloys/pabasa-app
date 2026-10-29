import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Card from '@/Components/Card';
import { Head } from '@inertiajs/react';
import DeleteUserForm from './Partials/DeleteUserForm';
import UpdatePasswordForm from './Partials/UpdatePasswordForm';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm';

export default function Edit({ mustVerifyEmail, status }) {
    return (
        <AuthenticatedLayout
            header={
                <div>
                    <h2 className="text-2xl font-bold tracking-tight text-slate-900">
                        Account Settings & Profile
                    </h2>
                    <p className="mt-1 text-sm text-slate-500">
                        Manage your Pabasa identity, credentials, and account security
                    </p>
                </div>
            }
        >
            <Head title="Profile Settings" />

            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-8">
                <Card className="max-w-3xl">
                    <Card.Content>
                        <UpdateProfileInformationForm
                            mustVerifyEmail={mustVerifyEmail}
                            status={status}
                        />
                    </Card.Content>
                </Card>

                <Card className="max-w-3xl">
                    <Card.Content>
                        <UpdatePasswordForm />
                    </Card.Content>
                </Card>

                <Card className="max-w-3xl border-rose-100">
                    <Card.Content>
                        <DeleteUserForm />
                    </Card.Content>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
