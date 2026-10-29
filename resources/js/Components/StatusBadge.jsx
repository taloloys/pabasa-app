export default function StatusBadge({ status, role, className = '' }) {
    let colorClasses = 'bg-stone-100 text-stone-700 border-stone-200';
    let label = status || role || '';
    let dotColor = 'bg-stone-400';

    // Status mapping
    switch (status) {
        case 'active':
        case 'published':
        case 'verified':
            colorClasses = 'bg-emerald-50 text-emerald-800 border-emerald-200/80 ring-1 ring-emerald-500/10';
            dotColor = 'bg-emerald-500';
            break;
        case 'invited':
        case 'pending_verification':
        case 'draft':
            colorClasses = 'bg-amber-50 text-amber-900 border-amber-200/80 ring-1 ring-amber-500/15';
            dotColor = 'bg-amber-500';
            break;
        case 'suspended':
            colorClasses = 'bg-rose-50 text-rose-800 border-rose-200/80 ring-1 ring-rose-500/10';
            dotColor = 'bg-rose-500';
            break;
        case 'revoked':
        case 'deactivated':
        case 'disabled':
            colorClasses = 'bg-stone-100 text-stone-500 border-stone-200 line-through';
            dotColor = 'bg-stone-400';
            break;
    }

    // Role mapping
    if (role) {
        switch (role) {
            case 'central_admin':
                colorClasses = 'bg-liturgical-100 text-liturgical-950 border-liturgical-300 ring-1 ring-gold-400/30';
                label = 'Central Authority';
                dotColor = 'bg-liturgical-700';
                break;
            case 'church_admin':
                colorClasses = 'bg-amber-50/80 text-amber-950 border-gold-300/80 ring-1 ring-gold-500/20';
                label = 'Parish Admin';
                dotColor = 'bg-gold-600';
                break;
            case 'lector':
                colorClasses = 'bg-sky-50 text-sky-950 border-sky-200/80 ring-1 ring-sky-500/15';
                label = 'Liturgical Lector';
                dotColor = 'bg-sky-600';
                break;
            case 'parishioner':
                colorClasses = 'bg-emerald-50 text-emerald-950 border-emerald-200/80';
                label = 'Parishioner';
                dotColor = 'bg-emerald-600';
                break;
        }
    }

    // Format snake_case if raw status
    if (!role && label) {
        label = label.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
    }

    return (
        <span
            className={`inline-flex items-center space-x-1.5 rounded-full border px-2.5 py-0.5 text-xs font-semibold tracking-wide ${colorClasses} ${className}`}
        >
            <span className={`h-1.5 w-1.5 rounded-full ${dotColor}`} />
            <span>{label}</span>
        </span>
    );
}
