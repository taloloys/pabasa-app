import { DoveIcon } from '@/Components/Icons';

export default function EmptyState({
    icon = <DoveIcon className="w-7 h-7 text-gold-700" />,
    title = 'No items to display',
    description = 'There are no records found for this view.',
    action = null,
    className = '',
}) {
    return (
        <div
            className={`rounded-xl border border-dashed border-gold-200/80 bg-white/70 p-10 text-center shadow-xs ${className}`}
        >
            <div className="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-gold-50 border border-gold-200 text-gold-700 shadow-2xs">
                {icon}
            </div>
            <h3 className="mt-4 text-base font-semibold text-slate-800">
                {title}
            </h3>
            <p className="mx-auto mt-1 max-w-sm text-xs text-slate-500 leading-relaxed">
                {description}
            </p>
            {action && <div className="mt-6">{action}</div>}
        </div>
    );
}

