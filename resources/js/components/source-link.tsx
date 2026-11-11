export function SourceLink({
    href,
    children,
    label = children,
}: {
    href: string;
    children: string;
    label?: string;
}) {
    return (
        <a
            href={href}
            target="_blank"
            rel="noreferrer"
            aria-label={`${label} (opens in a new tab)`}
            className="underline underline-offset-2"
        >
            {children}
        </a>
    );
}
