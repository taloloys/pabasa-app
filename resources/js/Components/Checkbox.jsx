export default function Checkbox({ className = '', ...props }) {
    return (
        <input
            {...props}
            type="checkbox"
            className={
                'rounded-md border-stone-300 text-liturgical-800 shadow-2xs focus:ring-gold-400 focus:ring-offset-1 ' +
                className
            }
        />
    );
}
