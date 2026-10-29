export default function Card({ children, className = '', ...props }) {
    return (
        <div
            className={`rounded-xl border border-slate-200 bg-white shadow-xs overflow-hidden ${className}`}
            {...props}
        >
            {children}
        </div>
    );
}

Card.Header = function CardHeader({ children, className = '', ...props }) {
    return (
        <div
            className={`border-b border-slate-100 bg-slate-50/60 px-6 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 ${className}`}
            {...props}
        >
            {children}
        </div>
    );
};

Card.Title = function CardTitle({ children, className = '', ...props }) {
    return (
        <h3
            className={`text-base font-semibold text-slate-900 tracking-tight ${className}`}
            {...props}
        >
            {children}
        </h3>
    );
};

Card.Description = function CardDescription({ children, className = '', ...props }) {
    return (
        <p className={`text-xs text-slate-500 mt-0.5 ${className}`} {...props}>
            {children}
        </p>
    );
};

Card.Content = function CardContent({ children, className = '', ...props }) {
    return (
        <div className={`p-6 ${className}`} {...props}>
            {children}
        </div>
    );
};

Card.Footer = function CardFooter({ children, className = '', ...props }) {
    return (
        <div
            className={`border-t border-slate-100 bg-slate-50/40 px-6 py-3.5 flex items-center justify-end space-x-3 ${className}`}
            {...props}
        >
            {children}
        </div>
    );
};

