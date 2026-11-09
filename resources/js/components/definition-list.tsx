import type { ReactNode } from 'react';

type Props = {
    items: { term: string; description: ReactNode }[];
};

// Term and description rows separated by thin rules. Stacked on phones, two columns from md.
export function DefinitionList({ items }: Props) {
    return (
        <dl className="divide-y">
            {items.map((item) => (
                <div
                    key={item.term}
                    className="grid gap-1 py-4 first:pt-0 last:pb-0 md:grid-cols-[minmax(0,1fr)_minmax(0,2fr)] md:gap-6"
                >
                    <dt className="type-item">{item.term}</dt>
                    <dd className="type-body text-muted-foreground">
                        {item.description}
                    </dd>
                </div>
            ))}
        </dl>
    );
}
