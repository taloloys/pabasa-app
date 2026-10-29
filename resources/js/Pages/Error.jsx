import ApplicationLogo from '@/Components/ApplicationLogo';
import LocaleSwitcher from '@/Components/LocaleSwitcher';
import { ShieldIcon, ChurchIcon, ExclamationTriangleIcon } from '@/Components/Icons';
import { Head, Link } from '@inertiajs/react';

export default function ErrorPage({ status = 404, message = null }) {
    const title = {
        503: '503: Service Unavailable',
        500: '500: Server Error',
        404: '404: Page or Parish Not Found',
        403: '403: Access Restricted',
    }[status] || `Error ${status}`;

    const description = {
        503: 'Sorry, we are doing some liturgical maintenance. Please check back soon.',
        500: 'Whoops, something went wrong on our sanctuary servers. Our technical staff has been alerted.',
        404: 'The parish resource or page you requested could not be found or you do not have permission to view it.',
        403: 'You do not have the required staff ministry permissions to access this parish console or record.',
    }[status] || (message || 'An unexpected error occurred.');

    return (
        <div className="flex min-h-screen flex-col items-center justify-between bg-[#FAF8F5] text-slate-800 antialiased p-6">
            <header className="w-full max-w-4xl flex items-center justify-between">
                <Link href="/">
                    <ApplicationLogo className="h-10 w-10" />
                </Link>
                <LocaleSwitcher />
            </header>

            <div className="w-full max-w-md text-center py-12">
                <div className="mx-auto flex h-20 w-20 items-center justify-center rounded-2xl bg-amber-50 border border-amber-200 shadow-xs">
                    {status === 403 ? (
                        <ShieldIcon className="w-10 h-10 text-liturgical-900" />
                    ) : status === 404 ? (
                        <ChurchIcon className="w-10 h-10 text-amber-800" />
                    ) : (
                        <ExclamationTriangleIcon className="w-10 h-10 text-rose-700" />
                    )}
                </div>

                <div className="mt-6 inline-block rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold uppercase tracking-wider text-slate-600">
                    Status {status}
                </div>

                <h1 className="mt-3 text-2xl font-bold tracking-tight text-slate-900 font-serif">
                    {title}
                </h1>

                <p className="mt-2 text-sm text-slate-600 leading-relaxed">
                    {description}
                </p>

                <div className="mt-8 flex flex-col sm:flex-row items-center justify-center gap-3">
                    <Link
                        href="/"
                        className="w-full sm:w-auto inline-flex items-center justify-center rounded-xl bg-indigo-700 px-5 py-2.5 text-sm font-semibold text-white shadow-xs hover:bg-indigo-800 transition"
                    >
                        Return Home
                    </Link>
                    <Link
                        href="/dashboard"
                        className="w-full sm:w-auto inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50 transition"
                    >
                        Go to Dashboard
                    </Link>
                </div>
            </div>

            <footer className="text-center text-xs text-slate-400">
                Pabasa Church Reading & Intention Management Platform &copy; {new Date().getFullYear()}
            </footer>
        </div>
    );
}

