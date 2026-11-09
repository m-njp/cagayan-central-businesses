type Props = {
    title: string;
    subtitle?: string;
};

// The answer first (design.md, "Principles"). The title is one name with no separators.
export function PageHeader({ title, subtitle }: Props) {
    return (
        <header className="flex max-w-3xl flex-col gap-3">
            <h1 className="type-title">{title}</h1>
            {subtitle && <p className="type-subtitle">{subtitle}</p>}
        </header>
    );
}
