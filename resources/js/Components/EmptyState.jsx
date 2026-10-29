export default function EmptyState({
    icon = '🕊️',
    title = 'No items to display',
    description = 'There are no records found for this view.',
    action = null,
    className = '',
}) {
    return (
        <div
            className={`rounded-xl border border-dashed border-slate-200 bg-white/70 p-10 text-center shadow-xs ${className}`}
        >
            <div className="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-2xl">
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

