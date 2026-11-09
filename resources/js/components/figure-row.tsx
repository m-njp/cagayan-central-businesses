import { Card } from '@/components/ui/card';
import { cn } from '@/lib/utils';

type Props = {
    figures: { value: string; label: string }[];
    className?: string;
};

// Headline figures in one card. The 1 pixel gap shows the border color, so the dividers follow the cells as they wrap.
export function FigureRow({ figures, className }: Props) {
    return (
        <Card
            className={cn(
                'grid grid-cols-2 gap-px overflow-hidden bg-border py-0',
                className,
            )}
        >
            {figures.map((figure) => (
                <dl
                    key={figure.label}
                    className="flex flex-col-reverse justify-end gap-2 bg-card p-5 lg:p-6"
                >
                    <dt className="type-label text-muted-foreground">
                        {figure.label}
                    </dt>
                    <dd className="type-figure">{figure.value}</dd>
                </dl>
            ))}
        </Card>
    );
}
